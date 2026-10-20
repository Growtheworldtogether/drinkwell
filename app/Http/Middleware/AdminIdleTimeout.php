<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** An admin session ends after a stretch with no requests (SPEC 17.2). */
class AdminIdleTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $limit = (int) config('security.admin_idle_minutes') * 60;
        $last = (int) $request->session()->get('admin_last_seen', now()->timestamp);

        if ($limit > 0 && now()->timestamp - $last > $limit) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'You were signed out after a period of inactivity.');
        }

        $request->session()->put('admin_last_seen', now()->timestamp);

        return $next($request);
    }
}
