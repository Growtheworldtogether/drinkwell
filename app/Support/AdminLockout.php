<?php

namespace App\Support;

use App\Enums\Role;
use App\Mail\SecurityAlert;
use App\Models\User;
use App\Services\MailDispatcher;
use Illuminate\Support\Facades\Cache;

/**
 * Five consecutive failed sign-ins on one admin email lock the form for 15 minutes, whatever the address
 * they come from (SPEC 17.2). The lock shows the same message as any other failed sign-in.
 */
final class AdminLockout
{
    public static function isLocked(string $email): bool
    {
        return Cache::has(self::lockKey($email));
    }

    public static function seconds(string $email): int
    {
        return max(1, (int) config('security.admin_lockout_minutes') * 60);
    }

    public static function isAdminEmail(string $email): bool
    {
        return User::query()->where('email', self::norm($email))->where('role', Role::Admin)->exists();
    }

    /** Count a failure. Returns true when this one tripped the lock. */
    public static function fail(string $email): bool
    {
        $email = self::norm($email);

        if (! self::isAdminEmail($email)) {
            return false;
        }

        $window = self::seconds($email);
        $count = Cache::add(self::countKey($email), 1, $window) ? 1 : (int) Cache::increment(self::countKey($email));

        if ($count < (int) config('security.admin_lockout_attempts')) {
            return false;
        }

        Cache::put(self::lockKey($email), true, $window);
        Cache::forget(self::countKey($email));

        $minutes = (int) config('security.admin_lockout_minutes');
        logger()->channel('security')->warning('Admin sign-in locked', ['email' => $email, 'minutes' => $minutes]);
        app(MailDispatcher::class)->sendToAdmins(fn () => new SecurityAlert($email, $count, $minutes), 'security');

        return true;
    }

    public static function clear(string $email): void
    {
        Cache::forget(self::countKey(self::norm($email)));
    }

    private static function norm(string $email): string
    {
        return strtolower(trim($email));
    }

    private static function lockKey(string $email): string
    {
        return 'admin-lock:'.sha1(self::norm($email));
    }

    private static function countKey(string $email): string
    {
        return 'admin-fails:'.sha1(self::norm($email));
    }
}
