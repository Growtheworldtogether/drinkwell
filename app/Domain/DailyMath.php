<?php

namespace App\Domain;

use App\Enums\Tier;

/**
 * One day of money for one machine, in integer cents. The fee is drawn from the tier's band with the seeded generator,
 * keyed by serial number and date, so a replay gives the same numbers. The pool is kept even so the 50/50 split has no
 * odd cent.
 */
final class DailyMath
{
    /** @return array{gross: int, fee: int, pool: int, share: int} */
    public static function day(Tier $tier, string $serial, string $date): array
    {
        $cfg = $tier->cfg();
        $gross = $tier->grossCents();
        $spread = $cfg['fee_spread_cents'];
        $low = $cfg['fee_center_cents'] - $spread;

        $fee = $low + (new Prng("fee:{$serial}:{$date}"))->below($spread * 2 + 1);

        if (($gross - $fee) % 2 !== 0) {
            $fee += $fee < $cfg['fee_center_cents'] + $spread ? 1 : -1;
        }

        $pool = $gross - $fee;

        return ['gross' => $gross, 'fee' => $fee, 'pool' => $pool, 'share' => intdiv($pool, 2)];
    }

    /** Days until the stake is back, rounding up, at a given daily payout. */
    public static function paybackDays(int $stakeCents, int $dailyPayoutCents): int
    {
        return (int) ceil($stakeCents / $dailyPayoutCents);
    }

    /** The lowest and highest daily payout a tier can produce. @return array{0: int, 1: int} */
    public static function band(Tier $tier): array
    {
        $cfg = $tier->cfg();
        $gross = $tier->grossCents();
        $max = (int) ceil(($gross - ($cfg['fee_center_cents'] - $cfg['fee_spread_cents'])) / 2);
        $min = (int) floor(($gross - ($cfg['fee_center_cents'] + $cfg['fee_spread_cents'])) / 2);

        return [$min, $max];
    }
}
