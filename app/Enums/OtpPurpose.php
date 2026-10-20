<?php

namespace App\Enums;

enum OtpPurpose: string
{
    case VerifyEmail = 'verify_email';
    case ResetPassword = 'reset_password';

    public function label(): string
    {
        return match ($this) {
            self::VerifyEmail => 'Verify email',
            self::ResetPassword => 'Reset password',
        };
    }

    public function requiresSubject(): bool
    {
        return false;
    }
}
