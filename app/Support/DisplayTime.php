<?php

namespace App\Support;

/** The name of the timezone the platform's business days run in, for labels next to times and dates. */
final class DisplayTime
{
    /** For example "Central time (CDT)". Other timezones show their name and abbreviation. */
    public static function label(): string
    {
        $tz = (string) config('drinkwell.timezone');
        $abbr = now($tz)->format('T');

        $name = match ($tz) {
            'America/New_York' => 'Central time',
            'America/New_York' => 'Eastern time',
            'America/Denver' => 'Mountain time',
            'America/Los_Angeles' => 'Pacific time',
            'UTC' => 'UTC',
            default => str_replace('_', ' ', $tz),
        };

        return $name === 'UTC' ? 'UTC' : "{$name} ({$abbr})";
    }
}
