<?php

use App\Http\Middleware\AdminIdleTimeout;
use App\Http\Middleware\AdminIpAllowlist;
use App\Http\Middleware\EnsureActive;
use App\Http\Middleware\EnsureEmailVerifiedByOtp;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'active' => EnsureActive::class,
            'verified.otp' => EnsureEmailVerifiedByOtp::class,
            'admin.ip' => AdminIpAllowlist::class,
            'admin.idle' => AdminIdleTimeout::class,
        ]);

        // Load balancers and CDNs: list their addresses in TRUSTED_PROXIES, or * to trust the one in front of you.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES') === '*' ? '*' : array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '')))));

        // Every response, including a 404 that matched no route, carries the security headers.
        $middleware->append(SecurityHeaders::class);

        // A password change invalidates the sessions on other devices (SPEC 5.3).
        $middleware->web(append: [AuthenticateSession::class, \Illuminate\Routing\Middleware\ThrottleRequests::class.':global']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['account_number', 'routing_number', 'code', 'otp']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
