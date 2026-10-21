<?php

namespace App\Enums;

enum WithdrawalStatus: string
{
    case AwaitingConfirmation = 'awaiting_confirmation';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingConfirmation => 'Awaiting your code',
            self::Pending => 'Waiting for approval',
            self::Approved => 'Approved and paid',
            self::Rejected => 'Declined',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Pending and approved requests have already left the balance. */
    public function holdsMoney(): bool
    {
        return in_array($this, [self::Pending, self::Approved], true);
    }
}
