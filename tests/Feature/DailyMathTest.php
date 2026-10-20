<?php

use App\Domain\DailyMath;
use App\Enums\Tier;


it('keeps the fixed gross: capacity times the drink price', function () {
    expect(Tier::Commercial->grossCents())->toBe(36_000)
        ->and(Tier::Enterprise->grossCents())->toBe(78_075)
        ->and(Tier::Commercial->stakeCents())->toBe(500_000)
        ->and(Tier::Enterprise->stakeCents())->toBe(1_000_000);
});

it('splits the pool exactly and keeps every payout inside the tier band', function (Tier $tier, int $lo, int $hi) {
    $seen = [];
    for ($d = 0; $d < 400; $d++) {
        $date = now()->startOfYear()->addDays($d)->toDateString();
        $x = DailyMath::day($tier, 'SER-1', $date);

        expect($x['gross'] - $x['fee'])->toBe($x['pool'])
            ->and($x['pool'] % 2)->toBe(0)
            ->and($x['share'] * 2)->toBe($x['pool'])
            ->and($x['share'])->toBeGreaterThanOrEqual($lo)
            ->and($x['share'])->toBeLessThanOrEqual($hi);
        $seen[$x['share']] = true;
    }
    // The payout really varies from day to day.
    expect(count($seen))->toBeGreaterThan(20);
})->with([
    'commercial' => [Tier::Commercial, 17_000, 18_000],
    'enterprise' => [Tier::Enterprise, 37_000, 39_000],
]);

it('stays inside the bands required by the brief', function () {
    [$cl, $ch] = DailyMath::band(Tier::Commercial);
    [$el, $eh] = DailyMath::band(Tier::Enterprise);

    expect($cl)->toBeGreaterThanOrEqual(17_000)->and($ch)->toBeLessThanOrEqual(18_000)
        ->and($el)->toBeGreaterThanOrEqual(36_000)->and($eh)->toBeLessThanOrEqual(40_000);
});

it('is repeatable for the same machine and date, and differs between machines', function () {
    $a = DailyMath::day(Tier::Commercial, 'A', '2026-03-03');
    $b = DailyMath::day(Tier::Commercial, 'A', '2026-03-03');

    expect($a)->toBe($b);

    $others = collect(range(1, 12))->map(fn ($i) => DailyMath::day(Tier::Commercial, "M{$i}", '2026-03-03')['fee'])->unique();
    expect($others->count())->toBeGreaterThan(1);
});

it('pays the deposit back within thirty days even on the worst day', function () {
    expect(DailyMath::paybackDays(500_000, 17_000))->toBeLessThanOrEqual(30)
        ->and(DailyMath::paybackDays(1_000_000, 37_000))->toBeLessThanOrEqual(30)
        ->and(round(500_000 / 17_500, 1))->toBe(28.6)
        ->and(round(1_000_000 / 38_000, 1))->toBe(26.3);
});
