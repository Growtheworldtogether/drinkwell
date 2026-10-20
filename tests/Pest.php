<?php

use App\Enums\Role;
use App\Enums\Tier;
use App\Models\User;
use App\Models\VendingMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// The form guard's minimum fill time would fail every test that posts a form at once.
pest()->beforeEach(function () {
    config(['security.min_fill_seconds' => 0]);
    $this->withSession(['auth.password_confirmed_at' => time()]);
})->in('Feature');

/** An open machine of a tier, with a clock that is easy to reason about. */
function machine(Tier $tier = Tier::Commercial, array $over = []): VendingMachine
{
    static $n = 0;
    $n++;

    return VendingMachine::create($over + [
        'serial_no' => sprintf('T-%s-%04d', $tier === Tier::Commercial ? 'C' : 'E', $n),
        'tier' => $tier,
        'capacity' => $tier->capacity(),
        'facility_name' => 'Test Hospital',
        'facility_type' => 'Hospital',
        'city' => 'Boston, MA',
        'latitude' => 42.3631,
        'longitude' => -71.0686,
        'stock' => $tier->capacity(),
        'hardware_state' => 'online',
        'assignment_status' => 'open',
        'operating_date' => now(config('drinkwell.timezone'))->toDateString(),
        'day_minute' => 0,
        'clock_synced_at' => now(),
    ]);
}

function signedIn(Role $role = Role::Applicant, array $over = []): User
{
    $user = User::factory()->role($role)->create($over);
    test()->actingAs($user);

    return $user;
}

function fakeMail(): void
{
    Mail::fake();
}
