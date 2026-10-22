<?php

use App\Models\VendingMachine;
use App\Services\TileCache;
use Database\Seeders\MachineSeeder;

it('adds only missing facilities when the machine list is seeded again', function () {
    (new MachineSeeder)->run();
    $first = VendingMachine::count();
    (new MachineSeeder)->run();

    expect($first)->toBe(35)
        ->and(VendingMachine::count())->toBe(35)
        ->and(VendingMachine::distinct()->count('city'))->toBe(21)
        ->and(VendingMachine::pluck('serial_no')->unique()->count())->toBe(35);
});

it('serves country view tiles only around the machines', function () {
    (new MachineSeeder)->run();
    $tiles = app(TileCache::class);

    [$x, $y] = TileCache::tileFor(39.5, -98.35, 4);
    expect($tiles->allowed(4, $x, $y))->toBeTrue();

    [$x, $y] = TileCache::tileFor(48.85, 2.35, 4);
    expect($tiles->allowed(4, $x, $y))->toBeFalse();

    [$x, $y] = TileCache::tileFor(48.85, 2.35, 14);
    expect($tiles->allowed(14, $x, $y))->toBeFalse();

    [$x, $y] = TileCache::tileFor(25.7905, -80.2120, 14);
    expect($tiles->allowed(14, $x, $y))->toBeTrue();
});
