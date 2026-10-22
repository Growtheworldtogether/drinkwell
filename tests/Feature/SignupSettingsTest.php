<?php

use App\Enums\Role;
use App\Enums\Tier;
use App\Models\InviteCode;
use App\Models\PairingRequest;
use App\Models\User;
use App\Services\PairingService;

$member = fn (array $over = []) => $over + ['name' => 'Ruth Carter', 'email' => 'ruth@example.com', 'password' => 'Ruth-Test-2026x', 'password_confirmation' => 'Ruth-Test-2026x'];

it('refuses sign-up without a reference code, or with a wrong one', function () use ($member) {
    $this->post(route('register'), $member())->assertSessionHasErrors('reference_code');
    $this->post(route('register'), $member(['reference_code' => 'DW-NOPE-NOPE']))->assertSessionHasErrors('reference_code');
    expect(User::where('email', 'ruth@example.com')->exists())->toBeFalse();
});

it('signs up with a valid code, ignores case and missing dashes, and counts the use', function () use ($member) {
    $code = InviteCode::create(['code' => 'DW-AB23-CD45', 'max_uses' => 1]);
    $this->post(route('register'), $member(['reference_code' => 'dwab23cd45']))->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'ruth@example.com')->first();
    expect($user->invite_code_id)->toBe($code->id)->and($code->fresh()->uses)->toBe(1)->and($code->fresh()->status())->toBe('Used up');

    auth()->logout();
    $this->post(route('register'), $member(['email' => 'sam@example.com', 'reference_code' => 'DW-AB23-CD45']))->assertSessionHasErrors('reference_code');
});

it('does not accept turned off or expired codes', function () use ($member) {
    InviteCode::create(['code' => 'DW-OFFF-2345', 'revoked_at' => now()]);
    InviteCode::create(['code' => 'DW-OLDD-2345', 'expires_at' => now()->subDay()]);
    $this->post(route('register'), $member(['reference_code' => 'DW-OFFF-2345']))->assertSessionHasErrors('reference_code');
    $this->post(route('register'), $member(['reference_code' => 'DW-OLDD-2345']))->assertSessionHasErrors('reference_code');
});

it('lets an administrator make and turn off codes', function () {
    signedIn(Role::Admin);
    $this->post(route('admin.settings.codes.create'), ['how_many' => 3, 'max_uses' => 2, 'note' => 'Church group'])->assertRedirect(route('admin.settings.codes'));
    expect(InviteCode::count())->toBe(3)->and(InviteCode::first()->code)->toMatch('/^DW-[A-Z2-9]{4}-[A-Z2-9]{4}$/');

    $code = InviteCode::first();
    $this->get(route('admin.settings.codes'))->assertOk()->assertSee($code->code)->assertSee('Church group');
    $this->post(route('admin.settings.codes.revoke', $code))->assertRedirect();
    expect($code->fresh()->status())->toBe('Turned off');
});

it('keeps code settings for administrators only', function () {
    signedIn(Role::Partner);
    $this->get(route('admin.settings.codes'))->assertForbidden();
    $this->post(route('admin.settings.codes.create'), ['how_many' => 1])->assertForbidden();
});

it('fills the pairing form for new members from the default deposit details', function () {
    signedIn(Role::Admin);
    $this->put(route('admin.settings.deposit.save'), ['partner_name' => 'Margaret Whitfield', 'account_holder' => 'Margaret Whitfield', 'bank_name' => 'Maple Trust', 'routing_number' => '12345-004', 'account_number' => '7654321'])
        ->assertRedirect(route('admin.settings.deposit'));

    machine();
    $applicant = User::factory()->role(Role::Applicant)->create();
    app(PairingService::class)->request($applicant, Tier::Commercial);

    $this->get(route('admin.pairings'))->assertOk()->assertSee('value="Maple Trust"', false)->assertSee('value="12345-004"', false);
});

it('accepts a Canadian bank account with transit and institution numbers', function () {
    \Illuminate\Support\Facades\Mail::fake();
    machine();
    $user = signedIn();

    \Livewire\Livewire::test(\App\Livewire\OnboardingWizard::class)
        ->set('name', 'Claire Tremblay')->set('dob', now()->subYears(60)->toDateString())->set('phone', '(416) 555-0199')->set('city', 'Toronto')
        ->call('next')->assertHasNoErrors()
        ->set('tier', 'commercial')->call('next')
        ->set('bankCountry', 'CA')->set('holderName', 'Claire Tremblay')->set('bankName', 'Maple Trust')
        ->set('transitNumber', '1234')->set('institutionNumber', '004')
        ->set('accountNumber', '7654321')->set('accountNumberConfirmation', '7654321')
        ->call('next')->assertHasErrors(['transitNumber'])
        ->set('transitNumber', '12345')
        ->call('next')->assertHasNoErrors()->assertSet('step', 4)
        ->set('agreeTerms', true)->call('submit')->assertRedirect(route('onboard.status'));

    $account = \App\Models\PayoutAccount::where('user_id', $user->id)->first();
    expect($account->country)->toBe('CA')->and($account->routing_number)->toBe('12345-004')->and($account->last4)->toBe('4321');
});
