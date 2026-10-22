<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

/**
 * Trusts the proxy in front of the site (a tunnel, a load balancer, Cloudflare) as set in TRUSTED_PROXIES.
 * The setting is read on each request, after .env has loaded; reading it in bootstrap/app.php happens too early
 * and always saw nothing, so pages behind an HTTPS tunnel linked their styles over plain http.
 */
class TrustProxiesFromConfig extends TrustProxies
{
    public function handle(Request $request, \Closure $next)
    {
        $value = trim((string) config('security.trusted_proxies', ''));
        $this->proxies = $value === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', $value))));

        return parent::handle($request, $next);
    }
}
