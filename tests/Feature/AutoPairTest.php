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

it('pairs on the server when the member opens the page, plays the search, then reveals the co-partner', function () use ($defaults, $waiting) {
    Mail::fake();
    machine();
    $defaults();
    $user = $waiting();

    $this->get(route('onboard.status'))->assertOk()->assertSee('Looking for your co-partner')->assertSessionHas('reveal_pending');

    $req = PairingRequest::where('user_id', $user->id)->first();
    expect($req->status)->toBe(PairingRequest::PAIRED)->and($req->bank_name)->toBe('Maple Trust')->and($req->account_last4)->toBe('4321')
        ->and(MachineOwnership::where('user_id', $user->id)->exists())->toBeTrue();
    Mail::assertSent(PairingDossier::class);

    // The reload at the end of the search reveals the co-partner once.
    $this->get(route('onboard.status'))->assertOk()->assertSee('We found your co-partner')->assertSee('Margaret Whitfield')->assertSee('Maple Trust');
    $this->get(route('onboard.status'))->assertOk()->assertDontSee('We found your co-partner')->assertSee('You are paired');

    // The old end-of-search address changes nothing now.
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

    $this->get(route('onboard.status'))->assertOk()->assertSee('Finding a partner')->assertDontSee('Looking for your co-partner');
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

it('explains on the System page why automatic pairing is or is not working', function () use ($defaults) {
    signedIn(Role::Admin);
    session(['auth.password_confirmed_at' => time()]);
    $this->get(route('admin.settings.system'))->assertSee('Waiting for these default details');
    machine();
    $defaults();
    $this->get(route('admin.settings.system'))->assertSee('No free machine for: Enterprise Hub Unit');
    machine(Tier::Enterprise);
    $this->get(route('admin.settings.system'))->assertSee('Automatic pairing is on');
    $defaults(false);
    $this->get(route('admin.settings.system'))->assertSee('Automatic pairing is off');
});
