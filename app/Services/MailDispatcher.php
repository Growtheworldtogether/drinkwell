<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Exceptions\MailSendFailed;
use App\Mail\BrandedMail;
use App\Models\MailLog;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * The only way mail leaves the app (SPEC 5.4, 9.4). Every send, success or
 * failure, writes a mail_log row.
 *
 * send() is for critical mail (OTP codes): it is synchronous and throws
 * MailSendFailed. sendQuietly() is for everything else: it never throws, so a
 * mail outage cannot roll back a settlement or an approval.
 */
class MailDispatcher
{
    /**
     * @throws MailSendFailed
     */
    public function send(BrandedMail $mail, string|User $to): void
    {
        // A code email is worth one more try: the first connection from a cold start (DNS, TLS) is the one that
        // most often fails, and a repeat of the same code email is harmless. Only the final result is logged.
        if (! $this->deliver($mail, $to, 2)) {
            throw new MailSendFailed($this->lastError);
        }
    }

    /**
     * A message addressed to a simulated co-partner. It is written to the mail log as "simulated" and never leaves the app.
     */
    public function logSimulated(BrandedMail $mail, User $to): void
    {
        $this->record($to, $to->email, $mail->template(), $mail->subjectText(), 'simulated', null);
    }

    /** Returns false instead of throwing. The failure is already logged. */
    public function sendQuietly(BrandedMail $mail, string|User $to): bool
    {
        try {
            return $this->deliver($mail, $to);
        } catch (Throwable $e) {
            // Logging itself failed (for example the database is down). Nothing more to do.
            Log::error('Mail logging failed', ['template' => $mail->template(), 'exception' => $e->getMessage()]);

            return false;
        }
    }

    /** The admin emails that Admin > Settings can switch off or send to one address. */
    public const ADMIN_TOPICS = [
        'application' => ['label' => 'New co-partner paired', 'help' => 'A senior finished onboarding and was paired with a machine.'],
        'funding' => ['label' => 'Partner reports a deposit', 'help' => 'A co-partner says they sent their deposit and it waits for confirmation.'],
        'contact' => ['label' => 'Contact form message', 'help' => 'Someone wrote to you from the Contact page.'],
        'withdrawal' => ['label' => 'Withdrawal requested', 'help' => 'A co-partner confirmed a withdrawal and it waits for approval.'],
        'security' => ['label' => 'Administrator sign-in locked', 'help' => 'Someone kept guessing an administrator password. Keep this on.'],
    ];

    /**
     * Send an admin notice. Admin > Settings can switch each kind off ($topic) or give it its own address. Otherwise it
     * goes to the company email saved in Admin, Settings, Branding (the support email), as one message. Until a real
     * company address is saved (the install default is a placeholder that cannot receive mail), it goes to every active
     * administrator instead, so a notice is never lost. Returns how many were sent.
     *
     * @param  callable(): BrandedMail  $make
     */
    public function sendToAdmins(callable $make, ?string $topic = null): int
    {
        $own = null;

        if ($topic !== null) {
            $settings = app(SettingsRepository::class);

            if (! $settings->bool("notify.{$topic}.enabled", true)) {
                return 0;
            }

            $own = $settings->nullableString("notify.{$topic}.email");
        }

        $company = $own ?? $this->companyEmail();

        if ($company !== null) {
            return $this->sendQuietly($make(), $company) ? 1 : 0;
        }

        $sent = 0;

        User::query()
            ->where('role', Role::Admin)
            ->where('status', UserStatus::Active)
            ->get()
            ->each(function (User $admin) use ($make, &$sent) {
                $sent += $this->sendQuietly($make(), $admin) ? 1 : 0;
            });

        return $sent;
    }

    /** The saved company email, or null while it is still the install default or not a usable address. */
    public function companyEmail(): ?string
    {
        $email = trim(app(BrandService::class)->supportEmail());

        if ($email === '' || strcasecmp($email, (string) config('drinkwell.brand.support_email')) === 0) {
            return null;
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || preg_match('/\.(test|invalid|example|localhost)$/i', $email)) {
            return null;
        }

        return $email;
    }

    private ?Throwable $lastError = null;

    private function deliver(BrandedMail $mail, string|User $to, int $attempts = 1): bool
    {
        $this->lastError = null;

        $user = $to instanceof User ? $to : User::query()->where('email', $to)->first();
        $email = $to instanceof User ? $to->email : $to;
        $subject = $mail->subjectText();

        for ($try = 1; $try <= $attempts; $try++) {
            try {
                Mail::to($email)->send($mail);
                $e = null;
                break;
            } catch (Throwable $caught) {
                $e = $caught;
                if ($try < $attempts) {
                    usleep(800_000);
                }
            }
        }

        if ($e !== null) {
            $this->lastError = $e;
            Log::error('Email send failed', ['template' => $mail->template(), 'to' => $email, 'exception' => $e->getMessage()]);
            $this->record($user, $email, $mail->template(), $subject, 'failed', $e->getMessage(), $this->resendable($mail) ? Crypt::encryptString(serialize($mail)) : null);

            return false;
        }

        $this->record($user, $email, $mail->template(), $subject, 'sent', null);

        return true;
    }

    private function record(?User $user, string $email, string $template, string $subject, string $status, ?string $error, ?string $payload = null): void
    {
        MailLog::create([
            'user_id' => $user?->id,
            'to_email' => $email,
            'template' => $template,
            'subject' => mb_substr($subject, 0, 255),
            'status' => $status,
            'error' => $error === null ? null : mb_substr($error, 0, 1000),
            'payload' => $payload,
        ]);
    }

    /** Codes are never kept. Everything else can be sent again from the log. */
    private function resendable(BrandedMail $mail): bool
    {
        return ! str_starts_with($mail->template(), 'otp_');
    }

    /**
     * Send a failed message again to the same address. The old row keeps its history and loses its
     * stored copy; the new attempt writes its own row. Returns false when it fails again.
     */
    public function resend(MailLog $log): bool
    {
        if ($log->status !== 'failed' || $log->payload === null) {
            return false;
        }

        $mail = unserialize(Crypt::decryptString($log->payload), ['allowed_classes' => true]);
        if (! $mail instanceof BrandedMail) {
            return false;
        }

        $log->forceFill(['payload' => null])->save();

        return $this->sendQuietly($mail, $log->user ?? $log->to_email);
    }
}
