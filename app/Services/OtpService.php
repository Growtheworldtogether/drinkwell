<?php

namespace App\Services;

use App\Enums\OtpPurpose;
use App\Exceptions\MailSendFailed;
use App\Exceptions\OtpException;
use App\Mail\BrandedMail;
use App\Mail\OtpResetPassword;
use App\Mail\OtpWithdrawal;
use App\Mail\OtpVerifyEmail;
use App\Models\OtpCode;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * One-time codes (SPEC 5.2). Six digits from random_int, stored only as an HMAC,
 * valid for 10 minutes, 5 wrong tries, single use, bound to an email and a purpose.
 */
class OtpService
{
    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public const COOLDOWN_SECONDS = 60;

    public const HOURLY_CAP = 5;

    public function __construct(private MailDispatcher $mail) {}

    /**
     * Create a code and email it, synchronously.
     *
     * If the transport fails nothing is stored, older codes stay valid, and
     * MailSendFailed is thrown for the caller to show.
     *
     * @param  array{amount_cents?: int}  $context  Extra text for the email (withdrawal amount).
     *
     * @throws OtpException when the resend cooldown or the hourly cap applies
     * @throws MailSendFailed
     */
    public function issue(string $email, OtpPurpose $purpose, ?int $subjectId = null, array $context = []): void
    {
        $email = self::normalize($email);

        if ($purpose->requiresSubject() && $subjectId === null) {
            throw new InvalidArgumentException('A withdrawal code needs the withdrawal id.');
        }

        $this->guardRates($email, $purpose);

        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

        $otp = OtpCode::create([
            'email' => $email,
            'purpose' => $purpose,
            'subject_id' => $purpose->requiresSubject() ? $subjectId : null,
            'code_hash' => self::hash($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        try {
            $this->mail->send($this->mailable($purpose, $code, $context), $email);
        } catch (MailSendFailed $e) {
            $otp->delete();

            throw $e;
        }

        if (config('drinkwell.show_codes')) {
            session(['demo_code' => $code]);
        }

        // Only now are older unconsumed codes for this email and purpose invalidated.
        OtpCode::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->where('id', '<', $otp->id)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);
    }

    /**
     * Check a code and consume it.
     *
     * @throws OtpException
     */
    public function verify(string $email, OtpPurpose $purpose, string $code, ?int $subjectId = null): OtpCode
    {
        $email = self::normalize($email);
        $code = preg_replace('/\s+/', '', $code) ?? '';

        $otp = OtpCode::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->when($purpose->requiresSubject(), fn ($q) => $q->where('subject_id', $subjectId))
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $otp || $otp->attempts >= self::MAX_ATTEMPTS) {
            throw OtpException::invalid();
        }

        if (! preg_match('/^\d{6}$/', $code) || ! hash_equals($otp->code_hash, self::hash($code))) {
            $otp->increment('attempts');
            $left = self::MAX_ATTEMPTS - $otp->attempts;

            if ($left <= 0) {
                $this->invalidate($otp);
            }

            throw OtpException::wrong($left);
        }

        // Single use: only one concurrent request can flip consumed_at.
        $consumed = OtpCode::query()->whereKey($otp->id)->whereNull('consumed_at')->update(['consumed_at' => now()]);

        if ($consumed === 0) {
            throw OtpException::invalid();
        }

        return $otp->refresh();
    }

    /** Expire every live code for this email and purpose. */
    public function invalidate(OtpCode|string $target, ?OtpPurpose $purpose = null): void
    {
        $query = OtpCode::query()->whereNull('consumed_at')->where('expires_at', '>', now());

        if ($target instanceof OtpCode) {
            $query->whereKey($target->id);
        } else {
            $query->where('email', self::normalize($target))->where('purpose', $purpose);
        }

        $query->update(['expires_at' => now()]);
    }

    /** Seconds until another code may be requested, 0 when allowed. */
    public function cooldownRemaining(string $email, OtpPurpose $purpose): int
    {
        $latest = OtpCode::query()
            ->where('email', self::normalize($email))
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();

        if (! $latest) {
            return 0;
        }

        return max(0, self::COOLDOWN_SECONDS - (now()->timestamp - $latest->created_at->timestamp));
    }

    public static function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    public static function normalize(string $email): string
    {
        return Str::lower(trim($email));
    }

    /** @throws OtpException */
    private function guardRates(string $email, OtpPurpose $purpose): void
    {
        $lastHour = OtpCode::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->where('created_at', '>', now()->subHour())
            ->count();

        if ($lastHour >= self::HOURLY_CAP) {
            throw OtpException::hourlyLimit();
        }

        $wait = $this->cooldownRemaining($email, $purpose);

        if ($wait > 0) {
            throw OtpException::cooldown($wait);
        }
    }

    /** @param  array{amount_cents?: int}  $context */
    private function mailable(OtpPurpose $purpose, string $code, array $context): BrandedMail
    {
        return match ($purpose) {
            OtpPurpose::VerifyEmail => new OtpVerifyEmail($code, self::TTL_MINUTES),
            OtpPurpose::ResetPassword => new OtpResetPassword($code, self::TTL_MINUTES),
            OtpPurpose::Withdrawal => new OtpWithdrawal($code, (int) ($context['amount_cents'] ?? 0), self::TTL_MINUTES),
        };
    }
}
