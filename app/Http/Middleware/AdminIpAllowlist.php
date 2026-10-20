<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Optional: only the addresses in ADMIN_IP_ALLOWLIST may reach /admin. Empty list means off. */
class AdminIpAllowlist
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = config('security.admin_ip_allowlist');

        if ($allowed !== [] && ! in_array($request->ip(), $allowed, true)) {
            logger()->channel('security')->warning('Admin address not allowed', ['ip' => $request->ip(), 'path' => $request->path()]);
            abort(403);
        }

        return $next($request);
    }
}
