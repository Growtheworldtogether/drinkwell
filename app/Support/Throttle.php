<?php

namespace App\Support;

use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;

/** A per-person limit inside a service call, answered with the same 429 page as the route limiters. */
final class Throttle
{
    public static function hit(string $key, int $max, int $seconds): void
    {
        if (RateLimiter::tooManyAttempts($key, $max)) {
            throw new ThrottleRequestsException('Too many requests.', null, ['Retry-After' => RateLimiter::availableIn($key)]);
        }

        RateLimiter::hit($key, $seconds);
    }
}
