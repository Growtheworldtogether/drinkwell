<?php

use App\Enums\Role;
use App\Mail\ContactMessage;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('renders every administrator page', function (string $route, array $params = []) {
    machine();
    signedIn(Role::Admin);
    $this->get(route($route, $params))->assertOk()->assertDontSee('pf-page');
})->with([
    ['admin.overview'], ['admin.machines'], ['admin.pairings'], ['admin.withdrawals'], ['admin.settings'],
    ['admin.settings.branding'], ['admin.audit'], ['admin.emails'], ['admin.integrity'],
]);

it('renders a machine page for an administrator', function () {
    $m = machine();
    signedIn(Role::Admin);
    $this->get(route('admin.machines.show', $m))->assertOk()->assertSee('Demo tools');
});

it('shows the contact page with the form and the fixed footer link', function () {
    $this->get(route('contact'))->assertOk()->assertSee('Send message')->assertSee('How your deposit works')->assertDontSee('Paying the deposit');
});

it('sends a contact message to the administrators and sets the reply address', function () {
    Mail::fake();
    User::factory()->role(Role::Admin)->create();
    $this->post(route('contact.send'), ['name' => 'Ruth Carter', 'email' => 'ruth@example.com', 'topic' => 'My deposit', 'message' => 'When will my deposit be confirmed?'])
        ->assertRedirect(route('contact'))->assertSessionHas('status');
    Mail::assertSent(ContactMessage::class, fn ($m) => $m->hasReplyTo('ruth@example.com'));
});

it('validates the contact form and ignores bots', function () {
    Mail::fake();
    $this->post(route('contact.send'), ['name' => '', 'email' => 'nope', 'topic' => 'x', 'message' => 'hi'])->assertSessionHasErrors(['name', 'email', 'topic', 'message']);
    User::factory()->role(Role::Admin)->create();
    $this->post(route('contact.send'), ['name' => 'Bot', 'email' => 'b@example.com', 'topic' => 'My deposit', 'message' => 'Buy cheap stuff now please', 'website' => 'http://spam'])->assertRedirect();
    Mail::assertNothingSent();
});
