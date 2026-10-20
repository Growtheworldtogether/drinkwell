<?php

use App\Domain\DepletionCurve;


it('allocates exactly the capacity across the selling hours', function (int $cap) {
    $hours = DepletionCurve::hourly($cap);

    expect(array_sum($hours))->toBe($cap)
        ->and(min(array_keys($hours)))->toBe(6)
        ->and(max(array_keys($hours)))->toBe(23)
        ->and(min($hours))->toBeGreaterThanOrEqual(0);
})->with([160, 347, 1, 7, 1000]);

it('has sold nothing before opening and everything at 23:59', function (int $cap) {
    expect(DepletionCurve::soldBy($cap, 0))->toBe(0)
        ->and(DepletionCurve::soldBy($cap, 359))->toBe(0)
        ->and(DepletionCurve::soldBy($cap, 1439))->toBe($cap);
})->with([160, 347]);

it('never sells backwards and never exceeds capacity', function (int $cap) {
    $prev = 0;
    for ($m = 0; $m <= 1439; $m++) {
        $s = DepletionCurve::soldBy($cap, $m);
        expect($s)->toBeGreaterThanOrEqual($prev)->and($s)->toBeLessThanOrEqual($cap);
        $prev = $s;
    }
})->with([160, 347]);

it('peaks at the morning rush and at lunch', function () {
    $h = DepletionCurve::hourly(347);
    $night = $h[22];

    expect($h[8])->toBeGreaterThan($h[10])
        ->and($h[12])->toBeGreaterThan($h[15])
        ->and($h[8])->toBeGreaterThan($night * 3);
});

it('finds the minute a unit is sold, consistent with the curve', function () {
    foreach ([1, 40, 80, 160] as $unit) {
        $m = DepletionCurve::minuteOfUnit(160, $unit);
        expect(DepletionCurve::soldBy(160, $m))->toBeGreaterThanOrEqual($unit);
        if ($m > 0) {
            expect(DepletionCurve::soldBy(160, $m - 1))->toBeLessThan($unit);
        }
    }
    expect(DepletionCurve::minuteOfUnit(160, 160))->toBeLessThanOrEqual(1439);
});
