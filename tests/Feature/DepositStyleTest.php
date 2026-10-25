<?php

use App\Enums\Role;
use App\Enums\Tier;
use App\Http\Controllers\Admin\SignupSettingsController;
use App\Mail\PairingDossier;
use App\Models\PairingRequest;
use App\Services\PairingService;
use App\Services\SettingsRepository;
use App\Support\DepositStyle;
use Illuminate\Support\Facades\Mail;

$contactStyle = function (array $over = []) {
    $s = app(SettingsRepository::class);
    $s->set(SignupSettingsController::DEPOSIT_KEY, $over + ['partner_name' => 'Margaret Whitfield', 'partner_city' => 'Toronto, ON', 'partner_email' => 'margaret@drinkwell.example', 'account_holder' => '', 'bank_name' => '', 'routing_number' => '', 'account_number' => '', 'note' => '']);
    $s->set(SignupSettingsController::AUTO_PAIR_KEY, true);
    $s->set(DepositStyle::KEY, DepositStyle::CONTACT);
    $s->set(DepositStyle::INSTRUCTION_KEY, "Read this first.\n\n- Email {partner_name}.\n- Send {amount} only to them.");
    $s->set(DepositStyle::SUBJECT_KEY, 'Machine {machine}');
    $s->set(DepositStyle::BODY_KEY, "Hello {partner_name},\nI am {member_name} and will send {amount}. Ref {reference}. {unknown_word}");
};

$pairedMember = function () {
    machine();
    $user = signedIn(Role::Applicant, ['name' => 'Helen Brooks']);
    app(PairingService::class)->request($user, Tier::Commercial);
    test()->get(route('onboard.status'))->assertSee('Looking for your co-partner');

    return $user;
};

it('pairs without any email, then takes the member through the three screens', function () use ($contactStyle, $pairedMember) {
    Mail::fake();
    $contactStyle();
    $user = $pairedMember();

    Mail::assertNotSent(PairingDossier::class);
    $req = PairingRequest::where('user_id', $user->id)->first();
    expect($req->partner_email)->toBe('margaret@drinkwell.example')->and($req->account_number)->toBeNull();

    // The reload at the end of the search opens screen 1.
    $this->get(route('onboard.status'))->assertRedirect(route('onboard.steps', 1));
    $this->get(route('onboard.steps', 1))->assertOk()->assertSee('match confirmed')
        ->assertSee('Margaret Whitfield')->assertSee('Toronto, ON')->assertSee('margaret@drinkwell.example')->assertDontSee('Account number');

    // Screen 3 needs the tick on screen 2.
    $this->get(route('onboard.steps', 3))->assertRedirect(route('onboard.steps', 2));
    $this->get(route('onboard.steps', 2))->assertOk()->assertSee('Important payment instruction')
        ->assertSee('<li>Email Margaret Whitfield.</li>', false)->assertSee('$5,000.00')->assertDontSee('Maple');
    $this->post(route('onboard.steps.read'))->assertSessionHasErrors('read');
    $this->post(route('onboard.steps.read'), ['read' => '1'])->assertRedirect(route('onboard.steps', 3));
    expect($req->fresh()->instructions_read_at)->not->toBeNull();

    $this->get(route('onboard.steps', 3))->assertOk()->assertSee('Message to your')
        ->assertSee('Margaret Whitfield')->assertSee('margaret@drinkwell.example')
        ->assertSee('I am Helen Brooks and will send $5,000.00', false)->assertSee('{unknown_word}')
        ->assertSee('mailto:margaret@drinkwell.example?subject=Machine%20', false);

    // Going through again asks for the tick again.
    $this->get(route('onboard.steps', 1))->assertOk();
    $this->get(route('onboard.steps', 3))->assertRedirect(route('onboard.steps', 2));

    // The progress page: a checklist with the next step, no bank details, and the receipt upload.
    $this->get(route('onboard.status'))->assertOk()->assertSee('What to do now')->assertSee('Next: email Margaret Whitfield')
        ->assertSee('Read it again')->assertSee('Open the message')->assertSee('Proof of payment')->assertDontSee('Account holder')->assertDontSee('Finding a partner');

    // "I have emailed" saves the time and moves the checklist on.
    $this->post(route('onboard.emailed'))->assertRedirect(route('onboard.status'));
    expect($req->fresh()->partner_emailed_at)->not->toBeNull();
    $this->get(route('onboard.status'))->assertSee('Next: send your deposit')->assertSee('Upload my receipt');

    // After the receipt is reported, the page waits for the co-partner.
    $req->fresh()->forceFill(['deposit_reported_at' => now()])->save();
    auth()->user()->unsetRelation('pairingRequest');
    $this->get(route('onboard.status'))->assertSee('Waiting for Margaret Whitfield to confirm');
});

it('keeps style 1 as before: bank details on the page and in the email, no payment screens', function () {
    Mail::fake();
    machine();
    $s = app(SettingsRepository::class);
    $s->set(SignupSettingsController::DEPOSIT_KEY, ['partner_name' => 'Margaret Whitfield', 'partner_city' => 'Toronto', 'partner_email' => '', 'account_holder' => 'Margaret Whitfield', 'bank_name' => 'Maple Trust', 'routing_number' => '', 'account_number' => '7654321', 'note' => '']);
    $user = signedIn(Role::Applicant);
    app(PairingService::class)->request($user, Tier::Commercial);
    $this->get(route('onboard.status'));
    Mail::assertSent(PairingDossier::class);
    $this->get(route('onboard.status'))->assertOk()->assertSee('We found your co-partner')->assertSee('Maple Trust');
    $this->get(route('onboard.steps', 1))->assertRedirect(route('onboard.status'));
});

it('lets the administrator choose the style and write the texts', function () {
    signedIn(Role::Admin);
    $this->get(route('admin.settings.deposit'))->assertOk()->assertSee('How members pay')->assertSee('{member_name}');
    $this->put(route('admin.settings.deposit.save'), ['deposit_style' => 'contact', 'partner_name' => 'M W'])->assertSessionHasErrors('partner_email');
    $this->put(route('admin.settings.deposit.save'), ['deposit_style' => 'contact', 'partner_name' => 'M W', 'partner_email' => 'mw@drinkwell.example', 'payment_instruction' => 'Pay {partner_name}', 'email_subject' => 'Hi', 'email_body' => 'Body', 'auto_pair' => '1'])
        ->assertRedirect(route('admin.settings.deposit'));
    expect(DepositStyle::isContact())->toBeTrue()->and(DepositStyle::texts()['instruction'])->toBe('Pay {partner_name}')
        ->and(SignupSettingsController::autoPairReady())->toBeTrue();

    // Preview as a member, with the same tick rule.
    $this->get(route('admin.settings.deposit.preview', 1))->assertOk()->assertSee('Preview')->assertSee('M W');
    $this->get(route('admin.settings.deposit.preview', 3))->assertRedirect(route('admin.settings.deposit.preview', 2));
    $this->post(route('admin.settings.deposit.preview.read'), ['read' => '1'])->assertRedirect(route('admin.settings.deposit.preview', 3));
    $this->get(route('admin.settings.deposit.preview', 3))->assertOk()->assertSee('Message to your');
    $this->get(route('admin.settings.deposit.preview.progress'))->assertOk()->assertSee('What to do now')->assertSee('Next: email M W');
    $this->get(route('admin.settings.deposit.preview.progress', ['state' => 'sent']))->assertOk()->assertSee('Waiting for M W to confirm');
});

it('pairs by hand in style 2 without bank details and shows who read the instructions', function () use ($contactStyle) {
    Mail::fake();
    $contactStyle();
    app(SettingsRepository::class)->set(SignupSettingsController::AUTO_PAIR_KEY, false);
    machine();
    $member = \App\Models\User::factory()->role(Role::Applicant)->create(['name' => 'Frank Miles']);
    $req = app(PairingService::class)->request($member, Tier::Commercial);
    signedIn(Role::Admin);

    $this->post(route('admin.pairings.assign', $req), ['_pid' => $req->id, 'partner_name' => 'Margaret Whitfield', 'partner_email' => 'margaret@drinkwell.example'])->assertRedirect(route('admin.pairings'));
    Mail::assertNotSent(PairingDossier::class);
    $this->get(route('admin.pairings'))->assertSee('Has not read the instructions yet');
    $req->fresh()->forceFill(['instructions_read_at' => now()])->save();
    $this->get(route('admin.pairings'))->assertSee('Read the instructions on');
});

it('formats the instruction safely', function () {
    $html = DepositStyle::format("Hello <b>there</b>\nsecond line\n\n- one\n- two\nafter")->toHtml();
    expect($html)->toBe("<p>Hello &lt;b&gt;there&lt;/b&gt;<br>second line</p>\n<ul><li>one</li><li>two</li></ul>\n<p>after</p>");
});
