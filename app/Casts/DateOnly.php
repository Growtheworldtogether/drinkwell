<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A DATE column read as a Carbon at midnight and always written as `Y-m-d`.
 * The stock `date` cast writes a timestamp string for Carbon input, which on SQLite
 * defeats the unique keys on business dates.
 *
 * @implements CastsAttributes<Carbon, Carbon|string>
 */
class DateOnly implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        return $value === null ? null : Carbon::parse($value)->startOfDay();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : Carbon::parse($value)->toDateString();
    }

    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value instanceof Carbon ? $value->toDateString() : $value;
    }
}
