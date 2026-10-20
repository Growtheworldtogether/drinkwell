<?php

namespace App\Domain;

/**
 * The diurnal sales curve of one machine. Pure arithmetic on integers, no clock and no database.
 *
 * Sales run from 06:00. Each hour gets a share of the day's capacity in proportion to config('drinkwell.hour_weights'),
 * with the rounding done by largest remainder so the hourly units add up to the capacity exactly. Inside an hour the
 * units are spread evenly by minute. The last selling hour (23:00) ends at minute 1439, so the machine is empty at 23:59
 * for every capacity. Peaks sit at the morning transit rush (07 to 09) and lunch (12 to 14).
 */
final class DepletionCurve
{
    /** The minute of the day (23:59) by which every unit is sold. */
    public const SOLD_OUT_MINUTE = 1439;

    /** @return array<int, int> Units per hour, keyed by hour, summing to $capacity. */
    public static function hourly(int $capacity): array
    {
        $weights = (array) config('drinkwell.hour_weights');
        $total = array_sum($weights);
        $out = [];
        $fractions = [];
        $given = 0;

        foreach ($weights as $hour => $w) {
            $exact = $capacity * $w;
            $out[$hour] = intdiv($exact, $total);
            $fractions[$hour] = $exact % $total;
            $given += $out[$hour];
        }

        // Largest remainder first; ties go to the earlier hour so the result is stable.
        $order = array_keys($fractions);
        usort($order, fn ($a, $b) => $fractions[$b] <=> $fractions[$a] ?: $a <=> $b);

        for ($i = 0; $i < $capacity - $given; $i++) {
            $out[$order[$i]]++;
        }

        ksort($out);

        return $out;
    }

    /** Units sold from 00:00 up to and including the given minute of the day (0 to 1439). */
    public static function soldBy(int $capacity, int $minute): int
    {
        if ($minute >= self::SOLD_OUT_MINUTE) {
            return $capacity;
        }

        $sold = 0;
        foreach (self::hourly($capacity) as $hour => $units) {
            $start = $hour * 60;
            $end = $hour === 23 ? self::SOLD_OUT_MINUTE : ($hour + 1) * 60;

            if ($minute >= $end) {
                $sold += $units;

                continue;
            }

            if ($minute > $start) {
                $sold += intdiv($units * ($minute - $start), $end - $start);
            }

            break;
        }

        return $sold;
    }

    /** The first minute of the day at which at least $unit units (1-based) have been sold. */
    public static function minuteOfUnit(int $capacity, int $unit): int
    {
        $unit = max(1, min($capacity, $unit));
        $lo = 0;
        $hi = self::SOLD_OUT_MINUTE;

        while ($lo < $hi) {
            $mid = intdiv($lo + $hi, 2);
            if (self::soldBy($capacity, $mid) >= $unit) {
                $hi = $mid;
            } else {
                $lo = $mid + 1;
            }
        }

        return $lo;
    }
}
