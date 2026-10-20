<?php

use App\Enums\AssignmentStatus;
use App\Enums\Role;
use App\Enums\SettlementStatus;
use App\Enums\Tier;
use App\Exceptions\NoMachineAvailable;
use App\Livewire\OnboardingWizard;
use App\Mail\PairingDossier;
use App\Models\MachineOwnership;
use App\Models\MailLog;
use App\Models\User;
use App\Services\PairingService;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('pairs a new senior with a machine and a simulated co-partner at once', function () {
    Mail::fake();
    $m = machine();
    $user = User::factory()->create();

    $mine = app(PairingService::class)->pair($user, Tier::Commercial);

    expect($mine->slot_no)->toBe(1)
        ->and($mine->settlement_status)->toBe(SettlementStatus::PendingSettlement)
        ->and($mine->capital_cents)->toBe(500_000)
        ->and($m->fresh()->assignment_status)->toBe(AssignmentStatus::Paired)
        ->and($user->fresh()->role)->toBe(Role::Partner);

    $other = MachineOwnership::where('machine_id', $m->id)->where('slot_no', 2)->first();
    $partner = $other->user;
    expect($partner->role)->toBe(Role::Operator)
        ->and($partner->profile->is_simulated)->toBeTrue()
        ->and($partner->email)->toEndWith('@example.test')
        ->and($partner->profile->age_at_signup)->toBeGreaterThanOrEqual(51)
        ->and($other->settlement_status)->toBe(SettlementStatus::Settled);

    Mail::assertSent(PairingDossier::class, fn ($mail) => $mail->hasTo($user->email));
    // The message to the simulated partner is logged, never sent.
    expect(MailLog::where('status', 'simulated')->where('to_email', $partner->email)->count())->toBe(1);
    Mail::assertNotSent(PairingDossier::class, fn ($mail) => $mail->hasTo($partner->email));
});

it('never gives the same machine to two people', function () {
    Mail::fake();
    machine();
    $svc = app(PairingService::class);
    $svc->pair(User::factory()->create(), Tier::Commercial);

    expect(fn () => $svc->pair(User::factory()->create(), Tier::Commercial))->toThrow(NoMachineAvailable::class);
    expect(MachineOwnership::count())->toBe(2);
});

it('picks a machine of the chosen option only', function () {
    Mail::fake();
    machine(Tier::Commercial);
    $e = machine(Tier::Enterprise);

    $mine = app(PairingService::class)->pair(User::factory()->create(), Tier::Enterprise);

    expect($mine->machine_id)->toBe($e->id)->and($mine->capital_cents)->toBe(1_000_000);
});

it('refuses a second pairing for the same account', function () {
    Mail::fake();
    machine();
    machine();
    $user = User::factory()->create();
    $svc = app(PairingService::class);
    $svc->pair($user, Tier::Commercial);

    expect(fn () => $svc->pair($user->fresh(), Tier::Commercial))->toThrow(DomainException::class);
});

it('creates a different simulated co-partner for each pairing', function () {
    Mail::fake();
    machine();
    machine();
    $svc = app(PairingService::class);
    $a = $svc->pair(User::factory()->create(), Tier::Commercial);
    $b = $svc->pair(User::factory()->create(), Tier::Commercial);

    expect($svc->partnerOf($a)->id)->not->toBe($svc->partnerOf($b)->id);
});

it('settles an ownership once and starts earning the next day', function () {
    Mail::fake();
    $m = machine();
    $mine = app(PairingService::class)->pair(User::factory()->create(), Tier::Commercial);
    $svc = app(PairingService::class);

    $svc->settle($mine);
    $svc->settle($mine);
    $mine->refresh();

    expect($mine->settlement_status)->toBe(SettlementStatus::Settled)
        ->and($mine->earning_from->toDateString())->toBe($m->operating_date->copy()->addDay()->toDateString())
        ->and($m->fresh()->assignment_status)->toBe(AssignmentStatus::Active);
});

it('onboarding rejects anyone under 51 and stores nothing', function () {
    machine();
    $user = signedIn();

    Livewire::test(OnboardingWizard::class)
        ->set('name', 'Young Person')
        ->set('dob', now()->subYears(40)->toDateString())
        ->set('phone', '(555) 010-1234')
        ->call('next')
        ->assertHasErrors(['dob'])
        ->assertSet('step', 1);

    expect($user->fresh()->profile)->toBeNull();
});

it('onboarding accepts a 51 year old on the birthday and puts them in the queue for a partner', function () {
    Mail::fake();
    $m = machine();
    $user = signedIn();

    Livewire::test(OnboardingWizard::class)
        ->set('name', 'Pat Senior')
        ->set('dob', now()->subYears(51)->toDateString())
        ->set('phone', '(555) 010-1234')
        ->call('next')->assertHasNoErrors()->assertSet('step', 2)
        ->set('tier', 'commercial')
        ->call('next')->assertSet('step', 3)
        ->set('holderName', 'Pat Senior')->set('bankName', 'Test Bank')->set('routingNumber', '021000021')
        ->set('accountNumber', '123456789')->set('accountNumberConfirmation', '123456789')->set('agreeSim', true)
        ->call('next')->assertHasNoErrors()->assertSet('step', 4)
        ->set('agreeTerms', true)
        ->call('submit')
        ->assertRedirect(route('onboard.status'));

    $profile = $user->fresh()->profile;
    expect($profile->age_at_signup)->toBe(51)
        ->and($profile->kyc_status)->toBe('simulated_verified')
        ->and($user->payoutAccounts()->first()->last4)->toBe('6789')
        ->and(\Illuminate\Support\Facades\DB::table('payout_accounts')->value('account_number'))->not->toContain('123456789')
        ->and($user->pairingRequest->status)->toBe('finding')
        ->and($m->fresh()->assignment_status)->toBe(AssignmentStatus::Open);
});

it('onboarding turns away someone one day short of 51', function () {
    machine();
    signedIn();

    Livewire::test(OnboardingWizard::class)
        ->set('name', 'Almost Senior')
        ->set('dob', now()->subYears(51)->addDay()->toDateString())
        ->set('phone', '(555) 010-1234')
        ->call('next')
        ->assertHasErrors(['dob']);
});

it('rejects a bad routing number and mismatched account numbers', function () {
    machine();
    signedIn();

    Livewire::test(OnboardingWizard::class)
        ->set('step', 3)
        ->set('holderName', 'Pat')->set('bankName', 'Bank')->set('routingNumber', '123456789')
        ->set('accountNumber', '123456789')->set('accountNumberConfirmation', '987654321')->set('agreeSim', true)
        ->call('next')
        ->assertHasErrors(['routingNumber', 'accountNumberConfirmation']);
});
