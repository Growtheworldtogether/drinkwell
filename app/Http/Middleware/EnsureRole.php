<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `role:admin`, `role:investor`, `role:applicant,investor` (SPEC 5.1, 10).
 * Reads users.role on every request. A user outside the list gets a 403.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // A member whose deposit is not confirmed yet who opens a dashboard page is taken to their progress page.
        if ($user && $user->role === \App\Enums\Role::Applicant && in_array('partner', $roles, true)
            && ! in_array('applicant', $roles, true) && $request->isMethod('GET') && ! $request->expectsJson()) {
            return redirect()->route('onboard.status');
        }

        abort_unless($user && in_array($user->role->value, $roles, true), 403);

        return $next($request);
    }
}
