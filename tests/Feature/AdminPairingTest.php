<?php

use App\Enums\AssignmentStatus;
use App\Enums\Role;
use App\Enums\SettlementStatus;
use App\Enums\Tier;
use App\Mail\PairingDossier;
use App\Models\MachineOwnership;
use App\Models\PairingRequest;
use App\Models\User;
use App\Services\PairingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

function details(array $over = []): array
{
    return array_merge([
        'partner_name' => 'Margaret Whitfield', 'partner_city' => 'Atlanta, GA', 'account_holder' => 'Margaret Whitfield',
        'bank_name' => 'Harbor Federal Credit Union', 'routing_number' => '021000021', 'account_number' => '55501234567',
        'reference' => '', 'note' => 'Please send within 2 days.',
    ], $over);
}

function waitingMember(Tier $tier = Tier::Commercial): PairingRequest
{
    return app(PairingService::class)->request(User::factory()->create(), $tier);
}

it('shows a member who has asked for a partner the waiting message, with no details', function () {
    $user = signedIn();
    app(PairingService::class)->request($user, Tier::Commercial);

    $this->get(route('onboard.status'))->assertOk()
        ->assertSee('Finding a partner')->assertSee('1 business day')->assertDontSee('Account number');
});

it('lets an administrator pair a waiting member, and emails and shows the details', function () {
    Mail::fake();
    $m = machine();
    $req = waitingMember();
    signedIn(Role::Admin);

    $this->post(route('admin.pairings.assign', $req), details())->assertRedirect(route('admin.pairings'));

    $req->refresh();
    expect($req->status)->toBe('paired')
        ->and($req->account_last4)->toBe('4567')
        ->and($req->reference)->toBe(PairingService::reference(MachineOwnership::where('user_id', $req->user_id)->first()))
        ->and($m->fresh()->assignment_status)->toBe(AssignmentStatus::Paired)
        ->and($req->user->fresh()->role)->toBe(Role::Partner);

    $partner = app(PairingService::class)->partnerOf(MachineOwnership::where('user_id', $req->user_id)->first());
    expect($partner->name)->toBe('Margaret Whitfield');

    Mail::assertSent(PairingDossier::class, 1);

    $this->actingAs($req->user->fresh())->get(route('onboard.status'))->assertOk()
        ->assertSee('Margaret Whitfield')->assertSee('55501234567')->assertSee('Harbor Federal Credit Union')->assertSee('Please send within 2 days.');
    $this->get(route('hub.dashboard'))->assertOk()->assertSee('55501234567');
});

it('stores the account number encrypted', function () {
    Mail::fake();
    machine();
    $req = waitingMember();
    signedIn(Role::Admin);
    $this->post(route('admin.pairings.assign', $req), details());

    expect(DB::table('pairing_requests')->value('account_number'))->not->toContain('55501234567')
        ->and(DB::table('pairing_requests')->value('routing_number'))->not->toContain('021000021');
});

it('lets an administrator fix the details and re-sends the email', function () {
    Mail::fake();
    machine();
    $req = waitingMember();
    signedIn(Role::Admin);
    $this->post(route('admin.pairings.assign', $req), details());

    $this->put(route('admin.pairings.update', $req), details(['account_number' => '99900011122', 'partner_name' => 'Margaret T. Whitfield']))->assertRedirect(route('admin.pairings'));

    expect($req->fresh()->account_number)->toBe('99900011122')->and($req->fresh()->account_last4)->toBe('1122');
    Mail::assertSent(PairingDossier::class, 2);
    $this->actingAs($req->user->fresh())->get(route('onboard.status'))->assertSee('99900011122')->assertDontSee('55501234567');
});

it('tells the administrator when no machine is free, and leaves the member waiting', function () {
    Mail::fake();
    $req = waitingMember();
    signedIn(Role::Admin);

    $this->post(route('admin.pairings.assign', $req), details())->assertSessionHasErrors('machine', null, 'p'.$req->id);
    expect($req->fresh()->status)->toBe('finding');
});

it('validates the pairing form and keeps it for administrators only', function () {
    machine();
    $req = waitingMember();

    $this->actingAs(User::factory()->role(Role::Partner)->create())->post(route('admin.pairings.assign', $req), details())->assertForbidden();

    signedIn(Role::Admin);
    $this->post(route('admin.pairings.assign', $req), details(['account_number' => '']))->assertSessionHasErrors('account_number', null, 'p'.$req->id);
    expect($req->fresh()->status)->toBe('finding');
});

it('records "I sent my deposit" once, and the administrator confirms it', function () {
    Mail::fake();
    machine();
    $req = waitingMember();
    $admin = signedIn(Role::Admin);
    $this->post(route('admin.pairings.assign', $req), details());

    $this->actingAs($req->user->fresh())->post(route('onboard.report'))->assertRedirect(route('onboard.status'));
    $first = $req->fresh()->deposit_reported_at;
    expect($first)->not->toBeNull();

    $this->post(route('onboard.report'));
    expect($req->fresh()->deposit_reported_at->equalTo($first))->toBeTrue();

    $this->actingAs($admin)->get(route('admin.pairings'))->assertOk()->assertSee('Member says it was sent');
    $own = MachineOwnership::where('user_id', $req->user_id)->first();
    $this->post(route('admin.ownerships.confirm', $own))->assertRedirect();
    expect($own->fresh()->settlement_status)->toBe(SettlementStatus::Settled);
});

it('no longer has a demo shortcut for members to confirm their own deposit', function () {
    expect(\Illuminate\Support\Facades\Route::has('onboard.demo-confirm'))->toBeFalse();
});

it('stores a payment proof privately, rejects bad files, and serves it only to the owner and admins', function () {
    Mail::fake();
    \Illuminate\Support\Facades\Storage::fake('local');
    machine();
    $req = waitingMember();
    $admin = signedIn(Role::Admin);
    $this->post(route('admin.pairings.assign', $req), details());
    $member = $req->user->fresh();

    $this->actingAs($member)->post(route('onboard.report'), ['proof' => \Illuminate\Http\UploadedFile::fake()->create('x.exe', 10)])->assertSessionHasErrors('proof');
    $this->post(route('onboard.report'), ['proof' => \Illuminate\Http\UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf')])->assertSessionHasErrors('proof');

    $this->post(route('onboard.report'), ['proof' => \Illuminate\Http\UploadedFile::fake()->image('receipt.jpg'), 'sent_on' => now()->toDateString()])->assertSessionDoesntHaveErrors();
    $path = $req->fresh()->proof_path;
    expect($path)->not->toBeNull();
    \Illuminate\Support\Facades\Storage::disk('local')->assertExists($path);

    $this->get(route('onboard.proof'))->assertOk();
    $this->actingAs($admin)->get(route('admin.pairings.proof', $req))->assertOk();
    $this->actingAs(User::factory()->role(Role::Partner)->create())->get(route('admin.pairings.proof', $req))->assertForbidden();

    $this->actingAs($member)->post(route('onboard.report'), ['proof' => \Illuminate\Http\UploadedFile::fake()->image('new.png')]);
    \Illuminate\Support\Facades\Storage::disk('local')->assertMissing($path);
});

it('keeps demo tools off the member dashboard and behind admin only', function () {
    $this->actingAs(User::factory()->role(Role::Partner)->create())->post(route('admin.demo'), [])->assertForbidden();
});
