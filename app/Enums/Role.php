<?php

namespace App\Enums;

enum Role: string
{
    case Applicant = 'applicant';
    case Partner = 'partner';
    case Operator = 'operator';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Applicant => 'Applicant',
            self::Partner => 'Co-partner',
            self::Operator => 'Simulated co-partner',
            self::Admin => 'Administrator',
        };
    }

    /** Simulated co-partners are bookkeeping accounts: they hold slot 2 but can never sign in. */
    public function canSignIn(): bool
    {
        return $this !== self::Operator;
    }

    public function homeRoute(): ?string
    {
        return match ($this) {
            self::Admin => 'admin.overview',
            self::Partner => 'hub.dashboard',
            self::Applicant => 'onboard',
            self::Operator => null,
        };
    }
}
