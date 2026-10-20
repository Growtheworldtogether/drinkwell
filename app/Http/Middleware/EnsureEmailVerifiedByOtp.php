<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** `verified.otp`: the account must have confirmed its email with a code (SPEC 5.1, 10). */
class EnsureEmailVerifiedByOtp
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->email_verified_at === null) {
            if ($request->expectsJson()) {
                abort(403, 'Your email address is not verified.');
            }

            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
