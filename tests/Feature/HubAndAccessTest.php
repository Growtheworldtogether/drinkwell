<?php

use App\Enums\Role;
use App\Enums\Tier;
use App\Models\DailyDistribution;
use App\Models\MachineOwnership;
use App\Models\User;
use App\Services\MachineEngine;
use App\Services\PairingService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Mail;

function pairedPartner(Tier $tier = Tier::Commercial, bool $settle = true): array
{
    Mail::fake();
    $m = machine($tier);
    $u = User::factory()->create();
    $mine = app(PairingService::class)->pair($u, $tier);
    if ($settle) {
        app(PairingService::class)->settle($mine);
    }

    return [$u->fresh(), $m->fresh(), $mine->fresh()];
}

it('serves the public pages', function (string $route) {
    machine();
    $this->get(route($route))->assertOk();
})->with(['home', 'program', 'terms', 'privacy']);

it('lists machines publicly without owners or money', function () {
    machine();
    $json = $this->getJson(route('public.machines'))->assertOk()->json();

    expect($json['machines'])->toHaveCount(1)
        ->and(array_keys($json['machines'][0]))->toBe(['serial', 'facility', 'type', 'city', 'tier', 'lat', 'lng', 'open']);
});

it('keeps the hub for partners only', function () {
    $this->get(route('hub.dashboard'))->assertRedirect(route('login'));

    signedIn(Role::Applicant);
    $this->get(route('hub.dashboard'))->assertForbidden();
});

it('shows a partner their own dashboard with the toolbar and the ledger', function () {
    [$u, $m] = pairedPartner();
    app(MachineEngine::class)->fastForward($m);
    $this->actingAs($u);

    $this->get(route('hub.dashboard'))
        ->assertOk()
        ->assertSee($m->serial_no)
        ->assertDontSee('Fast-forward 24 hours')
        ->assertDontSee('Trigger sales burst')
        ->assertDontSee('Driver restock')
        ->assertSee('Download CSV');
});

it('returns live telemetry as json', function () {
    [$u, $m] = pairedPartner();
    $this->actingAs($u);

    $this->getJson(route('hub.telemetry'))->assertOk()->assertJsonStructure([
        'serial', 'stock', 'capacity', 'fill_pct', 'state', 'clock', 'ticker', 'logs', 'can_restock', 'can_burst', 'paid_cents', 'payback_pct',
    ])->assertJsonPath('capacity', 160);
});

it('exports the ledger as csv with exact dollars', function () {
    [$u, $m] = pairedPartner();
    app(MachineEngine::class)->fastForward($m); // settles today (earning starts tomorrow, so nothing yet)
    app(MachineEngine::class)->fastForward($m->fresh());
    $this->actingAs($u);

    $res = $this->get(route('hub.ledger.csv'))->assertOk();
    $csv = $res->streamedContent();
    $lines = array_filter(explode("\n", trim($csv)));

    expect($lines[0])->toBe('date,machine,units_sold,gross_usd,fee_usd,pool_usd,payout_usd,ach_ref')
        ->and(count($lines))->toBe(2)
        ->and($lines[1])->toMatch('/,160,360\.00,\d+\.\d\d,\d+\.\d\d,1[78]\d\.\d\d,ACH-/');
});

it('lets a partner run only their own machine', function () {
    [$u, $mine] = pairedPartner();
    $other = machine();
    $this->actingAs($u);

    $this->post(route('sim.sales-burst', $mine))->assertRedirect();
    expect($mine->fresh()->stock)->toBeLessThan($mine->stock);

    $this->post(route('sim.sales-burst', $other))->assertForbidden();
    $this->post(route('sim.advance-day', $other))->assertForbidden();
    $this->post(route('sim.driver-restock', $other))->assertForbidden();
});

it('keeps the toolbar away from applicants and anonymous visitors', function () {
    $m = machine();
    $this->post(route('sim.advance-day', $m))->assertRedirect(route('login'));
    signedIn(Role::Applicant);
    $this->post(route('sim.advance-day', $m))->assertForbidden();
});

it('lets an administrator run any machine, and records it', function () {
    $m = machine();
    $admin = signedIn(Role::Admin);

    $this->post(route('sim.sales-burst', $m))->assertRedirect();
    expect($m->fresh()->stock)->toBe(135)
        ->and(\App\Models\AdminAuditLog::where('admin_id', $admin->id)->where('action', 'sim.sales_burst')->count())->toBe(1);
});

it('explains a refused command instead of failing', function () {
    [$u, $m] = pairedPartner();
    $this->actingAs($u);

    $this->from(route('hub.dashboard'))->post(route('sim.driver-restock', $m))
        ->assertRedirect(route('hub.dashboard'))
        ->assertSessionHas('sim_error');
});

it('lets an administrator confirm a deposit, and non-admins not', function () {
    [$u, $m, $mine] = pairedPartner(settle: false);

    $this->actingAs($u)->post(route('admin.ownerships.confirm', $mine))->assertForbidden();

    signedIn(Role::Admin);
    $this->post(route('admin.ownerships.confirm', $mine))->assertRedirect();
    expect($mine->fresh()->settlement_status->value)->toBe('settled');

    $this->get(route('admin.overview'))->assertOk();
    $this->get(route('admin.machines'))->assertOk();
    $this->get(route('admin.machines.show', $m))->assertOk();
    $this->post(route('admin.integrity.check'))->assertOk()->assertSee('Passed');
});

it('seeds the demo with Arthur Pendelton and twenty days of payouts', function () {
    $this->seed(DatabaseSeeder::class);
    $arthur = User::where('email', 'arthur.pendelton@example.test')->first();

    expect($arthur->name)->toBe('Arthur Pendelton')
        ->and($arthur->profile->age_at_signup)->toBe(64)
        ->and(DailyDistribution::where('user_id', $arthur->id)->count())->toBe(20)
        ->and(\App\Models\VendingMachine::count())->toBe(32)
        ->and(MachineOwnership::count())->toBe(12);

    // Every payout is inside its band and the integrity checks agree.
    expect(array_filter(app(\App\Services\Invariants::class)->run()))->toBe([]);

    // Every pin has plausible US coordinates.
    \App\Models\VendingMachine::all()->each(fn ($m) => expect($m->latitude)->toBeBetween(25, 49)->and($m->longitude)->toBeBetween(-125, -66));
});
