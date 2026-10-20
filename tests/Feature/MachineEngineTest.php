<?php

use App\Domain\DepletionCurve;
use App\Enums\Role;
use App\Enums\SettlementStatus;
use App\Enums\Tier;
use App\Exceptions\SimulationRefused;
use App\Models\DailyDistribution;
use App\Models\MachineDay;
use App\Models\MachineOwnership;
use App\Models\User;
use App\Services\Invariants;
use App\Services\MachineEngine;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

function own($machine, string $status = 'settled', int $slot = 1, ?string $from = null): MachineOwnership
{
    $user = User::factory()->role(Role::Partner)->create();

    return MachineOwnership::create([
        'machine_id' => $machine->id, 'user_id' => $user->id, 'slot_no' => $slot,
        'capital_cents' => $machine->tier->stakeCents(), 'settlement_status' => $status,
        'earning_from' => $from ?? $machine->operating_date->toDateString(),
    ]);
}

it('settles a day once, and a second call changes nothing', function () {
    $m = machine();
    own($m);
    own($m, slot: 2);
    $engine = app(MachineEngine::class);

    $first = $engine->settleDay($m);
    $second = $engine->settleDay($m);

    expect($first)->not->toBeNull()
        ->and($second)->toBeNull()
        ->and(MachineDay::count())->toBe(1)
        ->and(DailyDistribution::count())->toBe(2)
        ->and($first->units_sold)->toBe(160);
});

it('refuses a duplicate payout at the database level', function () {
    $m = machine();
    $o = own($m);
    $day = app(MachineEngine::class)->settleDay($m);
    $row = DailyDistribution::first();

    expect(fn () => DailyDistribution::create([
        'machine_day_id' => $day->id, 'machine_id' => $m->id, 'user_id' => $o->user_id, 'ownership_id' => $o->id,
        'distribution_date' => $row->distribution_date, 'payout_cents' => $row->payout_cents, 'ach_ref' => 'OTHER-REF',
    ]))->toThrow(QueryException::class);
});

it('pays only confirmed owners who are earning on that date', function () {
    $m = machine();
    $paid = own($m);
    own($m, 'pending_settlement', 2);
    app(MachineEngine::class)->settleDay($m);

    expect(DailyDistribution::pluck('user_id')->all())->toBe([$paid->user_id]);

    $late = machine();
    own($late, 'settled', 1, $late->operating_date->copy()->addDay()->toDateString());
    app(MachineEngine::class)->settleDay($late);
    expect(DailyDistribution::where('machine_id', $late->id)->count())->toBe(0);
});

it('fast-forwards: closes and pays today, opens tomorrow with a full restock', function () {
    $m = machine();
    own($m);
    own($m, slot: 2);
    $today = $m->operating_date->toDateString();

    $m = app(MachineEngine::class)->fastForward($m);

    expect(MachineDay::count())->toBe(1)
        ->and(MachineDay::first()->operating_date->toDateString())->toBe($today)
        ->and($m->operating_date->toDateString())->toBe(Carbon::parse($today)->addDay()->toDateString())
        ->and($m->stock)->toBe(160)
        ->and($m->day_minute)->toBe(0)
        ->and(DailyDistribution::count())->toBe(2);

    // Twice in a row closes two different days and never the same day twice.
    app(MachineEngine::class)->fastForward($m);
    expect(MachineDay::count())->toBe(2);
    expect(array_filter(app(Invariants::class)->run()))->toBe([]);
});

it('pulls forward twenty-five sales, or what is left', function () {
    $m = machine();
    $engine = app(MachineEngine::class);

    expect($engine->salesBurst($m)->stock)->toBe(135);

    $m->update(['stock' => 10]);
    expect($engine->salesBurst($m)->stock)->toBe(0)
        ->and($m->fresh()->hardware_state->value)->toBe('sold_out');

    expect(fn () => $engine->salesBurst($m))->toThrow(SimulationRefused::class);
});

it('restocks only a sold-out machine, back to what the curve still has to sell', function () {
    $m = machine(over: ['day_minute' => 600, 'stock' => 100]);
    $engine = app(MachineEngine::class);

    expect(fn () => $engine->driverRestock($m))->toThrow(SimulationRefused::class);

    $m->update(['stock' => 0]);
    $r = $engine->driverRestock($m);

    expect($r->stock)->toBe(160 - DepletionCurve::soldBy(160, 600))
        ->and($r->stock)->toBeGreaterThan(0);
});

it('refuses a restock once the selling day is over', function () {
    $m = machine(over: ['day_minute' => 1439, 'stock' => 0, 'hardware_state' => 'sold_out']);

    expect(fn () => app(MachineEngine::class)->driverRestock($m))->toThrow(SimulationRefused::class);
});

it('sells down along the curve as the clock runs and is empty by 23:59', function () {
    config(['drinkwell.clock_speed' => 60.0]); // one real second is one simulated minute
    Carbon::setTestNow('2026-05-05 12:00:00');
    $m = machine(over: ['clock_synced_at' => now()]);
    $engine = app(MachineEngine::class);

    Carbon::setTestNow('2026-05-05 12:08:00'); // 8 real minutes = 480 simulated minutes = 08:00
    $m = $engine->sync($m);
    expect($m->day_minute)->toBe(480)
        ->and($m->stock)->toBe(160 - DepletionCurve::soldBy(160, 480));

    Carbon::setTestNow('2026-05-05 12:23:59'); // 1439 simulated minutes since midnight
    $m = $engine->sync($m);
    expect($m->day_minute)->toBe(1439)->and($m->stock)->toBe(0);
    Carbon::setTestNow();
});

it('rolls the day over by itself, settling exactly once', function () {
    config(['drinkwell.clock_speed' => 60.0]);
    Carbon::setTestNow('2026-05-05 12:00:00');
    $m = machine(over: ['clock_synced_at' => now()]);
    own($m);
    $date = $m->operating_date->toDateString();

    Carbon::setTestNow('2026-05-05 12:25:00'); // 1500 simulated minutes: past midnight by 60
    $engine = app(MachineEngine::class);
    $m = $engine->sync($m);
    $m = $engine->sync($m);

    expect(MachineDay::count())->toBe(1)
        ->and($m->operating_date->toDateString())->toBe(Carbon::parse($date)->addDay()->toDateString())
        ->and($m->day_minute)->toBe(60)
        ->and($m->stock)->toBe(160 - DepletionCurve::soldBy(160, 60));
    Carbon::setTestNow();
});

it('never lets stock rise above the curve ceiling after a burst', function () {
    $m = machine(over: ['day_minute' => 700, 'stock' => 160 - DepletionCurve::soldBy(160, 700)]);
    $engine = app(MachineEngine::class);
    $before = $m->stock;
    $m = $engine->salesBurst($m);
    $m = $engine->sync($m);

    expect($m->stock)->toBe($before - 25);
});
