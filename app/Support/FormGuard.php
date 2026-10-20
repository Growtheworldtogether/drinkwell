<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Honeypot and minimum fill time for public and money-adjacent forms (SPEC 17.2). A bot that fills the hidden
 * field, or posts faster than a person can type, is answered as if it had succeeded and the hit is logged.
 */
final class FormGuard
{
    public const FIELD = 'website';

    public const TOKEN = '_started';

    /** An opaque, unforgeable timestamp to put in the form when it is shown. */
    public static function token(): string
    {
        return Crypt::encryptString((string) now()->timestamp);
    }

    public static function passes(?string $honeypot, ?string $token, string $form, ?string $ip = null): bool
    {
        $ok = blank($honeypot);
        $min = (int) config('security.min_fill_seconds');

        // With the minimum at zero (tests, or switched off) a missing timestamp is not held against the form.
        if ($ok && $min > 0) {
            try {
                $ok = now()->timestamp - (int) Crypt::decryptString((string) $token) >= $min;
            } catch (Throwable) {
                $ok = false;
            }
        }

        if (! $ok) {
            logger()->channel('security')->warning('Form guard tripped', ['form' => $form, 'ip' => $ip ?? request()->ip()]);
        }

        return $ok;
    }

    public static function request(Request $request, string $form): bool
    {
        return self::passes($request->input(self::FIELD), $request->input(self::TOKEN), $form, $request->ip());
    }
}
