<?php

/*
| Security settings (SPEC 17). Everything here has a safe default; the .env keys are optional.
*/
return [

    // Start in report-only mode, watch the browser console for violations, then set this to true.
    'csp_enforce' => (bool) env('CSP_ENFORCE', false),

    // Reject passwords found in public breaches. Needs outbound HTTPS, so it is off unless you turn it on.
    'check_breached' => (bool) env('CHECK_BREACHED_PASSWORDS', false),

    // Admin sessions end after this many idle minutes.
    'admin_idle_minutes' => (int) env('ADMIN_IDLE_MINUTES', 60),

    // Comma list of addresses allowed to reach /admin. Empty means any address.
    'admin_ip_allowlist' => array_values(array_filter(array_map('trim', explode(',', (string) env('ADMIN_IP_ALLOWLIST', ''))))),

    // Consecutive failed sign-ins on one admin email before the form locks, and for how long.
    'admin_lockout_attempts' => 5,
    'admin_lockout_minutes' => 15,

    // A form submitted faster than this many seconds after it was shown is treated as a bot.
    'min_fill_seconds' => 3,

    'backups_keep' => 14,

    // Switches every named rate limiter off. Only honoured outside production: the page-width check opens hundreds of
    // pages from one address in minutes, which is exactly what the limiters are there to stop.
    'rate_limits_off' => (bool) env('RATE_LIMITS_OFF', false),
];
