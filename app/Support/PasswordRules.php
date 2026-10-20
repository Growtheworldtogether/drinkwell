<?php

namespace App\Support;

use App\Enums\Role;
use Illuminate\Validation\Rules\Password;

/** Password rules by role (SPEC 5.1, 17.2): 10 characters, admins 12, mixed case and a number, and not in a public breach when enabled. */
final class PasswordRules
{
    public const MIN = 10;

    public const ADMIN_MIN = 12;

    public static function for(?Role $role = null): Password
    {
        $rule = Password::min($role === Role::Admin ? self::ADMIN_MIN : self::MIN)->mixedCase()->numbers();

        return config('security.check_breached') ? $rule->uncompromised() : $rule;
    }
}
