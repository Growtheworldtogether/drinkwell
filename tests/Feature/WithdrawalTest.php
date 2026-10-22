<?php

use App\Enums\Role;
use App\Enums\Tier;
use App\Enums\WithdrawalStatus;
use App\Mail\WithdrawalApproved;
use App\Mail\WithdrawalRejected;
use App\Mail\WithdrawalRequestedAdmin;
use App\Models\DailyDistribution;
use App\Models\MachineDay;
use App\Models\OtpCode;
use App\Models\PayoutAccount;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\OtpService;
use App\Services\PairingService;
use App\Services\WithdrawalService;
use Illuminate\Support\Facades\Mail;

/** A settled partner who has earned $400 in payouts and has a payout account. */
function earner(int $cents = 40_000): User
{
    Mail::fake();
    $m = machine();
    $u = User::factory()->create();
    $mine = app(PairingService::class)->pair($u, Tier::Commercial);
    app(PairingService::class)->settle($mine);
    PayoutAccount::factory()->create(['user_id' => $u->id, 'is_default' => true, 'bank_name' => 'Harbor Federal', 'holder_name' => $u->name, 'last4' => '4321']);
    $day = MachineDay::create(['machine_id' => $m->id, 'operating_date' => '2026-10-01', 'units_sold' => 160, 'gross_cents' => 36000, 'fee_cents' => 1000, 'pool_cents' => 35000]);
    DailyDistribution::create(['machine_day_id' => $day->id, 'machine_id' => $m->id, 'user_id' => $u->id, 'ownership_id' => $mine->id, 'distribution_date' => '2026-10-01', 'payout_cents' => $cents, 'ach_ref' => 'ACH1']);

    return $u->fresh();
}

/** Ask for a withdrawal and confirm it with a known code. */
function confirmedWithdrawal(User $u, string $amount = '100.00'): Withdrawal
{
    test()->actingAs($u)->post(route('hub.withdraw.request'), ['amount' => $amount])->assertSessionDoesntHaveErrors();
    $w = Withdrawal::where('user_id', $u->id)->latest('id')->first();
    OtpCode::query()->latest('id')->first()->update(['code_hash' => OtpService::hash('123456')]);
    test()->post(route('hub.withdraw.confirm', $w), ['code' => '123456'])->assertSessionDoesntHaveErrors();

    return $w->fresh();
}

it('parses dollar amounts and refuses nonsense', function () {
    expect(WithdrawalService::parseDollars('$1,234.5'))->toBe(123450)
        ->and(WithdrawalService::parseDollars('50'))->toBe(5000)
        ->and(fn () => WithdrawalService::parseDollars('12.345'))->toThrow(\App\Exceptions\WithdrawalException::class)
        ->and(fn () => WithdrawalService::parseDollars('abc'))->toThrow(\App\Exceptions\WithdrawalException::class);
});

it('shows a member their balance and takes a request only after the emailed code', function () {
    $u = earner();
    User::factory()->role(Role::Admin)->create();
    $this->actingAs($u)->get(route('hub.withdraw'))->assertOk()->assertSee('$400.00')->assertSee('Harbor Federal');

    $this->post(route('hub.withdraw.request'), ['amount' => '100'])->assertRedirect(route('hub.withdraw'));
    $w = Withdrawal::first();
    expect($w->status)->toBe(WithdrawalStatus::AwaitingConfirmation)
        ->and(app(WithdrawalService::class)->balance($u))->toBe(40_000);

    $this->post(route('hub.withdraw.confirm', $w), ['code' => '000000'])->assertSessionHasErrors('code');
    expect($w->fresh()->status)->toBe(WithdrawalStatus::AwaitingConfirmation);

    OtpCode::query()->latest('id')->first()->update(['code_hash' => OtpService::hash('123456')]);
    $this->post(route('hub.withdraw.confirm', $w), ['code' => '123456'])->assertRedirect();
    expect($w->fresh()->status)->toBe(WithdrawalStatus::Pending)
        ->and(app(WithdrawalService::class)->balance($u))->toBe(30_000);
    Mail::assertSent(WithdrawalRequestedAdmin::class);
});

it('refuses too small, too large and badly typed amounts', function () {
    $u = earner();
    $this->actingAs($u);
    $this->post(route('hub.withdraw.request'), ['amount' => '10'])->assertSessionHasErrors('amount');
    $this->post(route('hub.withdraw.request'), ['amount' => '400.01'])->assertSessionHasErrors('amount');
    $this->post(route('hub.withdraw.request'), ['amount' => 'lots'])->assertSessionHasErrors('amount');
    expect(Withdrawal::count())->toBe(0);
});

it('does not let money be taken twice', function () {
    $u = earner();
    confirmedWithdrawal($u, '300');
    $this->post(route('hub.withdraw.request'), ['amount' => '150'])->assertSessionHasErrors('amount');
});

it('lets a member cancel a waiting request and gives the money back', function () {
    $u = earner();
    $w = confirmedWithdrawal($u);
    $this->delete(route('hub.withdraw.cancel', $w))->assertRedirect();
    expect($w->fresh()->status)->toBe(WithdrawalStatus::Cancelled)
        ->and(app(WithdrawalService::class)->balance($u))->toBe(40_000);
});

it('keeps one member out of another member\'s withdrawal', function () {
    $w = confirmedWithdrawal(earner());
    $this->actingAs(User::factory()->role(Role::Partner)->create())->delete(route('hub.withdraw.cancel', $w))->assertNotFound();
    $this->post(route('hub.withdraw.confirm', $w), ['code' => '123456'])->assertNotFound();
});

it('lets an administrator approve with a reference and emails the member', function () {
    $u = earner();
    $w = confirmedWithdrawal($u);
    $admin = signedIn(Role::Admin);

    $this->get(route('admin.withdrawals'))->assertOk()->assertSee($u->name)->assertSee('Approve and email the member');
    $this->post(route('admin.withdrawals.approve', $w), ['paid_reference' => ''])->assertSessionHasErrors('paid_reference');
    $this->post(route('admin.withdrawals.approve', $w), ['paid_reference' => 'ACH-778899'])->assertRedirect(route('admin.withdrawals'));

    expect($w->fresh()->status)->toBe(WithdrawalStatus::Approved)->and($w->fresh()->paid_reference)->toBe('ACH-778899')
        ->and(app(WithdrawalService::class)->balance($u))->toBe(30_000);
    Mail::assertSent(WithdrawalApproved::class);
    $this->assertDatabaseHas('admin_audit_logs', ['action' => 'withdrawal.approve', 'admin_id' => $admin->id]);

    // A second decision is refused.
    $this->post(route('admin.withdrawals.reject', $w), ['reason' => 'Too late now'])->assertSessionHasErrors();
    expect($w->fresh()->status)->toBe(WithdrawalStatus::Approved);
});

it('lets an administrator decline with a reason and returns the money', function () {
    $u = earner();
    $w = confirmedWithdrawal($u);
    signedIn(Role::Admin);

    $this->post(route('admin.withdrawals.reject', $w), ['reason' => 'no'])->assertSessionHasErrors();
    $this->post(route('admin.withdrawals.reject', $w), ['reason' => 'Account details do not match'])->assertRedirect(route('admin.withdrawals'));

    expect($w->fresh()->status)->toBe(WithdrawalStatus::Rejected)->and(app(WithdrawalService::class)->balance($u))->toBe(40_000);
    Mail::assertSent(WithdrawalRejected::class);
    $this->actingAs($u)->get(route('hub.withdraw'))->assertSee('Account details do not match');
});

it('keeps withdrawals for the right roles', function () {
    $w = confirmedWithdrawal(earner());
    $this->actingAs(User::factory()->role(Role::Partner)->create());
    $this->get(route('admin.withdrawals'))->assertForbidden();
    $this->post(route('admin.withdrawals.approve', $w), ['paid_reference' => 'x'])->assertForbidden();

    auth()->logout();
    $this->actingAs(User::factory()->role(Role::Applicant)->create())->get(route('hub.withdraw'))->assertRedirect(route('onboard.status'));
    $this->post(route('hub.withdraw.request'), ['amount' => '60'])->assertForbidden();
});

it('stops new requests while the switch is off, and leaves waiting ones alone', function () {
    $u = earner();
    $w = confirmedWithdrawal($u, '50');
    $admin = signedIn(Role::Admin);
    $this->post(route('admin.withdrawals.toggle'), ['enabled' => 0])->assertRedirect();

    $this->actingAs($u)->post(route('hub.withdraw.request'), ['amount' => '60'])->assertSessionHasErrors('amount');
    expect($w->fresh()->status)->toBe(WithdrawalStatus::Pending);

    $this->actingAs($admin)->post(route('admin.withdrawals.toggle'), ['enabled' => 1]);
    $this->travel(2)->minutes(); // past the one minute wait between codes
    $this->actingAs($u)->post(route('hub.withdraw.request'), ['amount' => '60'])->assertSessionDoesntHaveErrors();
});
