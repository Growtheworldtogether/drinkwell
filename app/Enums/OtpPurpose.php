<?php

namespace App\Enums;

enum OtpPurpose: string
{
    case VerifyEmail = 'verify_email';
    case ResetPassword = 'reset_password';
    case Withdrawal = 'withdrawal';

    public function label(): string
    {
        return match ($this) {
            self::VerifyEmail => 'Verify email',
            self::ResetPassword => 'Reset password',
            self::Withdrawal => 'Confirm withdrawal',
        };
    }

    public function requiresSubject(): bool
    {
        return $this === self::Withdrawal;
    }
}
