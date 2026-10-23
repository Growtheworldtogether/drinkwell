<?php

use App\Enums\Role;
use App\Enums\Tier;
use App\Http\Controllers\Admin\SignupSettingsController;
use App\Mail\PairingDossier;
use App\Models\MachineOwnership;
use App\Models\PairingRequest;
use App\Services\PairingService;
use App\Services\SettingsRepository;
use Illuminate\Support\Facades\Mail;

$defaults = fn (bool $auto = true) => tap(app(SettingsRepository::class), function ($s) use ($auto) {
    $s->set(SignupSettingsController::DEPOSIT_KEY, ['partner_name' => 'Margaret Whitfield', 'partner_city' => 'Toronto', 'account_holder' => 'Margaret Whitfield', 'bank_name' => 'Maple Trust', 'routing_number' => '12345-004', 'account_number' => '7654321', 'note' => '']);
    $s->set(SignupSettingsController::AUTO_PAIR_KEY, $auto);
});

$waiting = function () {
    $user = signedIn(Role::Applicant);
    app(PairingService::class)->request($user, Tier::Commercial);

    return $user;
};

it('shows the search, then pairs with the default details and reveals the co-partner', function () use ($defaults, $waiting) {
    Mail::fake();
    machine();
    $defaults();
    $user = $waiting();

    $this->get(route('onboard.status'))->assertOk()->assertSee('Looking for your co-partner')->assertSee(route('onboard.match'), false);

    $this->post(route('onboard.match'))->assertRedirect(route('onboard.status'))->assertSessionHas('just_paired');

    $req = PairingRequest::where('user_id', $user->id)->first();
    expect($req->status)->toBe(PairingRequest::PAIRED)->and($req->bank_name)->toBe('Maple Trust')->and($req->account_last4)->toBe('4321')
        ->and(MachineOwnership::where('user_id', $user->id)->exists())->toBeTrue();
    Mail::assertSent(PairingDossier::class);

    $this->withSession(['just_paired' => true])->get(route('onboard.status'))->assertOk()
        ->assertSee('We found your co-partner')->assertSee('Margaret Whitfield')->assertSee('Maple Trust');

    // A second request changes nothing.
    $this->post(route('onboard.match'))->assertRedirect(route('onboard.status'));
    expect(MachineOwnership::where('user_id', $user->id)->count())->toBe(1);
});

it('waits for the administrator when automatic pairing is off or the details are missing', function () use ($defaults, $waiting) {
    machine();
    $defaults(false);
    $user = $waiting();

    $this->get(route('onboard.status'))->assertOk()->assertSee('Finding a partner')->assertDontSee('Looking for your co-partner');
    $this->post(route('onboard.match'))->assertRedirect(route('onboard.status'));
    expect(MachineOwnership::where('user_id', $user->id)->exists())->toBeFalse();

    app(SettingsRepository::class)->set(SignupSettingsController::AUTO_PAIR_KEY, true);
    app(SettingsRepository::class)->set(SignupSettingsController::DEPOSIT_KEY, ['partner_name' => 'Margaret Whitfield']);
    $this->get(route('onboard.status'))->assertSee('Finding a partner');
});

it('falls back to waiting when no machine is free', function () use ($defaults, $waiting) {
    $defaults();
    $user = $waiting();

    $this->post(route('onboard.match'))->assertRedirect(route('onboard.status'))->assertSessionHas('auto_pair_failed');
    $this->get(route('onboard.status'))->assertSee('Finding a partner')->assertDontSee('Looking for your co-partner');
    expect(PairingRequest::where('user_id', $user->id)->value('status'))->toBe(PairingRequest::FINDING);
});

it('lets the administrator switch automatic pairing on and off', function () {
    signedIn(Role::Admin);
    $this->get(route('admin.settings.deposit'))->assertOk()->assertSee('Pair new members automatically');
    $this->put(route('admin.settings.deposit.save'), ['partner_name' => 'Margaret Whitfield', 'auto_pair' => '1'])
        ->assertSessionHas('status', fn ($s) => str_contains($s, 'Automatic pairing waits'));
    expect(SignupSettingsController::autoPairReady())->toBeFalse();
    $this->put(route('admin.settings.deposit.save'), ['partner_name' => 'M W', 'account_holder' => 'M W', 'bank_name' => 'B', 'account_number' => '123456', 'auto_pair' => '1']);
    expect(SignupSettingsController::autoPairReady())->toBeTrue();
    $this->put(route('admin.settings.deposit.save'), ['partner_name' => 'M W', 'account_holder' => 'M W', 'bank_name' => 'B', 'account_number' => '123456']);
    expect(SignupSettingsController::autoPairReady())->toBeFalse();
});
