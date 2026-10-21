<?php

namespace App\Services;

use App\Enums\OtpPurpose;
use App\Enums\WithdrawalStatus;
use App\Exceptions\MailSendFailed;
use App\Exceptions\OtpException;
use App\Exceptions\WithdrawalException;
use App\Mail\WithdrawalApproved;
use App\Mail\WithdrawalRejected;
use App\Mail\WithdrawalRequestedAdmin;
use App\Models\DailyDistribution;
use App\Models\User;
use App\Models\Withdrawal;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Withdrawals. The balance is every payout earned minus every withdrawal that is waiting or approved.
 * A request is first unconfirmed (nothing held). The emailed code turns it into a pending request, which
 * takes the amount out of the balance. Cancel and decline give it back. Nothing here moves real money: an
 * approval only records the payment reference the administrator typed in.
 */
class WithdrawalService
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly SettingsRepository $settings,
        private readonly MailDispatcher $mail,
    ) {}

    public static function minCents(): int
    {
        return (int) config('drinkwell.withdrawal_min_cents');
    }

    public function earned(User|int $user): int
    {
        return (int) DailyDistribution::query()->where('user_id', $user instanceof User ? $user->id : $user)->sum('payout_cents');
    }

    /** Money already taken out of the balance: pending plus approved. */
    public function taken(User|int $user): int
    {
        return (int) Withdrawal::query()->where('user_id', $user instanceof User ? $user->id : $user)
            ->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Approved])->sum('amount_cents');
    }

    public function balance(User|int $user): int
    {
        return $this->earned($user) - $this->taken($user);
    }

    /** "50", "50.00", "$1,234.5": dollars with up to two decimals. Anything else is refused. */
    public static function parseDollars(string $input): int
    {
        $clean = str_replace([',', '$', ' '], '', trim($input));

        if (! preg_match('/^\d{1,9}(\.\d{1,2})?$/', $clean)) {
            throw new WithdrawalException('Enter the amount in dollars, like 50.00.');
        }

        [$dollars, $cents] = array_pad(explode('.', $clean, 2), 2, '0');

        return ((int) $dollars) * 100 + (int) str_pad($cents, 2, '0');
    }

    public function enabled(): bool
    {
        return $this->settings->bool('withdrawals.enabled', true);
    }

    /**
     * Step 1: record the request and email the code. Nothing is held yet.
     *
     * @throws WithdrawalException
     * @throws OtpException
     * @throws MailSendFailed
     */
    public function request(User $user, int $amountCents): Withdrawal
    {
        if (! $this->enabled()) {
            throw new WithdrawalException('Withdrawals are paused for now. Please try again later.');
        }

        $account = $user->payoutAccounts()->where('is_default', true)->first() ?? $user->payoutAccounts()->first();

        if (! $account) {
            throw new WithdrawalException('Add a payout account first. Please contact support.');
        }

        if ($amountCents < self::minCents()) {
            throw new WithdrawalException('The smallest withdrawal is '.Money::format(self::minCents()).'.');
        }

        $available = $this->balance($user);

        if ($amountCents > $available) {
            throw new WithdrawalException('You can withdraw up to '.Money::format(max(0, $available)).'. Enter that amount or less.');
        }

        $withdrawal = DB::transaction(function () use ($user, $amountCents, $account) {
            // One unconfirmed request at a time. It holds nothing, so it is simply replaced.
            Withdrawal::query()->where('user_id', $user->id)->where('status', WithdrawalStatus::AwaitingConfirmation)->delete();

            return Withdrawal::query()->create([
                'user_id' => $user->id,
                'amount_cents' => $amountCents,
                'bank_name' => $account->bank_name,
                'holder_name' => $account->holder_name,
                'account_last4' => $account->last4,
                'status' => WithdrawalStatus::AwaitingConfirmation,
                'reference' => 'WD'.strtoupper(Str::random(14)),
            ]);
        });

        try {
            $this->otp->issue($user->email, OtpPurpose::Withdrawal, $withdrawal->id, ['amount_cents' => $amountCents]);
        } catch (OtpException|MailSendFailed $e) {
            $withdrawal->delete();

            throw $e;
        }

        return $withdrawal;
    }

    /** @throws OtpException @throws MailSendFailed @throws WithdrawalException */
    public function resend(Withdrawal $withdrawal): void
    {
        $this->assertOpen($withdrawal);
        $this->otp->issue($withdrawal->user->email, OtpPurpose::Withdrawal, $withdrawal->id, ['amount_cents' => $withdrawal->amount_cents]);
    }

    /**
     * Step 2: a correct code makes the request pending and takes the amount out of the balance.
     *
     * @throws OtpException
     * @throws WithdrawalException
     */
    public function confirm(Withdrawal $withdrawal, string $code): Withdrawal
    {
        $this->assertOpen($withdrawal);

        // Checked outside the transaction so a wrong try is still counted.
        $this->otp->verify($withdrawal->user->email, OtpPurpose::Withdrawal, $code, $withdrawal->id);

        $confirmed = DB::transaction(function () use ($withdrawal) {
            User::query()->whereKey($withdrawal->user_id)->lockForUpdate()->first();
            $fresh = Withdrawal::query()->whereKey($withdrawal->id)->lockForUpdate()->first();

            if (! $fresh || $fresh->status !== WithdrawalStatus::AwaitingConfirmation) {
                throw new WithdrawalException('This request was already confirmed or has expired.');
            }

            if ($this->balance($fresh->user_id) < $fresh->amount_cents) {
                $fresh->delete();

                throw new WithdrawalException('Your available balance is now below this amount. Request a smaller amount.');
            }

            $fresh->update(['status' => WithdrawalStatus::Pending, 'confirmed_at' => now()]);

            return $fresh->refresh();
        });

        $this->mail->sendToAdmins(fn () => new WithdrawalRequestedAdmin($confirmed->user->name, $confirmed->amount_cents, $confirmed->reference), 'withdrawal');

        return $confirmed;
    }

    /** The member takes back a request that is still waiting. */
    public function cancel(Withdrawal $withdrawal): Withdrawal
    {
        return $this->settle($withdrawal, WithdrawalStatus::Cancelled, null, null);
    }

    /** @throws WithdrawalException */
    public function approve(Withdrawal $withdrawal, User $admin, string $paidReference): Withdrawal
    {
        $paidReference = trim($paidReference);

        if ($paidReference === '' || mb_strlen($paidReference) > 64) {
            throw new WithdrawalException('Enter the payment reference, up to 64 characters.');
        }

        $done = DB::transaction(function () use ($withdrawal, $admin, $paidReference) {
            $fresh = $this->lockPending($withdrawal);
            $fresh->update(['status' => WithdrawalStatus::Approved, 'decided_by' => $admin->id, 'decided_at' => now(), 'paid_reference' => $paidReference]);

            return $fresh->refresh();
        });

        AuditLogger::log($admin, 'withdrawal.approve', $done, ['status' => 'pending'], ['status' => 'approved', 'paid_reference' => $paidReference]);
        $this->mail->sendQuietly(new WithdrawalApproved($done->amount_cents, $done->reference, $paidReference, $done->bank_name, $done->account_last4), $done->user);

        return $done;
    }

    /** @throws WithdrawalException */
    public function reject(Withdrawal $withdrawal, User $admin, string $reason): Withdrawal
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw new WithdrawalException('Give a reason of at least 5 characters. The member sees it.');
        }

        $done = $this->settle($withdrawal, WithdrawalStatus::Rejected, $admin, $reason);

        AuditLogger::log($admin, 'withdrawal.reject', $done, ['status' => 'pending'], ['status' => 'rejected', 'reason' => $reason]);
        $this->mail->sendQuietly(new WithdrawalRejected($done->amount_cents, $done->reference, $reason), $done->user);

        return $done;
    }

    private function settle(Withdrawal $withdrawal, WithdrawalStatus $to, ?User $admin, ?string $note): Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $to, $admin, $note) {
            $fresh = $this->lockPending($withdrawal);
            $fresh->update(['status' => $to, 'decided_by' => $admin?->id, 'decided_at' => now(), 'admin_note' => $note]);

            return $fresh->refresh();
        });
    }

    /** Lock the member and the row, and refuse anything that is not pending (double approve, approve after cancel). */
    private function lockPending(Withdrawal $withdrawal): Withdrawal
    {
        User::query()->whereKey($withdrawal->user_id)->lockForUpdate()->first();
        $fresh = Withdrawal::query()->whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

        if ($fresh->status !== WithdrawalStatus::Pending) {
            throw new WithdrawalException('This withdrawal is already '.strtolower($fresh->status->label()).'.');
        }

        return $fresh;
    }

    private function assertOpen(Withdrawal $withdrawal): void
    {
        $expired = $withdrawal->created_at->lt(now()->subMinutes(OtpService::TTL_MINUTES));

        if ($withdrawal->status !== WithdrawalStatus::AwaitingConfirmation || $expired) {
            throw new WithdrawalException('This request expired. Start a new withdrawal.');
        }
    }
}
