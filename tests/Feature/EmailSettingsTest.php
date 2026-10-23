<?php

use App\Enums\Role;
use App\Models\AdminAuditLog;
use App\Models\Setting;
use App\Services\MailSettings;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    signedIn(Role::Admin);
    session(['email_settings_opened_at' => now()->timestamp]);
});

$form = fn (array $over = []) => $over + ['enabled' => '1', 'host' => 'mail.drinkwell.example', 'port' => 465, 'username' => 'no-reply@drinkwell.example', 'password' => 'Mailbox-Secret-9', 'from_address' => '', 'from_name' => 'DRinkWeLL'];

it('saves SMTP details, encrypts the password, keeps it out of the audit log and uses them for mail', function () use ($form) {
    $this->get(route('admin.settings.email'))->assertOk()->assertSee('Email is not connected');

    $this->put(route('admin.settings.email.save'), $form())->assertRedirect(route('admin.settings.email'));

    $raw = Setting::find(MailSettings::KEY)->getRawOriginal('value');
    expect($raw)->not->toContain('Mailbox-Secret-9')
        ->and(AdminAuditLog::where('action', 'setting_updated')->latest('id')->first()->toJson())->not->toContain('Mailbox-Secret-9')
        ->and(config('mail.default'))->toBe('smtp')
        ->and(config('mail.mailers.smtp.host'))->toBe('mail.drinkwell.example')
        ->and(config('mail.mailers.smtp.scheme'))->toBe('smtps')
        ->and(config('mail.mailers.smtp.password'))->toBe('Mailbox-Secret-9')
        ->and(config('mail.from.address'))->toBe('no-reply@drinkwell.example');

    $this->get(route('admin.settings.email'))->assertSee('mail.drinkwell.example')->assertSee('Saved. Leave empty to keep it.')->assertDontSee('Mailbox-Secret-9');
});

it('keeps the saved password when the field is left empty, and uses STARTTLS on 587', function () use ($form) {
    $this->put(route('admin.settings.email.save'), $form());
    $this->put(route('admin.settings.email.save'), $form(['password' => '', 'port' => 587]));
    app(MailSettings::class)->apply();
    expect(config('mail.mailers.smtp.password'))->toBe('Mailbox-Secret-9')->and(config('mail.mailers.smtp.scheme'))->toBe('smtp');
});

it('checks the details', function () use ($form) {
    $this->put(route('admin.settings.email.save'), $form(['host' => 'https://mail.x.com/', 'port' => 80]))->assertSessionHasErrors(['host', 'port']);
});

it('sends a test email', function () use ($form) {
    Mail::fake();
    $this->post(route('admin.settings.email.test'), ['to' => 'me@drinkwell.example'])->assertSessionHas('error');
    $this->put(route('admin.settings.email.save'), $form());
    $this->post(route('admin.settings.email.test'), ['to' => 'me@drinkwell.example'])->assertSessionHas('status');
    Mail::assertSent(\App\Mail\MailConnectionTest::class);
});

it('asks for the developer code before showing or changing anything', function () use ($form) {
    session()->forget('email_settings_opened_at');

    $this->get(route('admin.settings.email'))->assertOk()->assertSee('Developer code needed')->assertDontSee('Mail server');
    $this->put(route('admin.settings.email.save'), $form())->assertRedirect(route('admin.settings.email'));
    expect(Setting::find(MailSettings::KEY))->toBeNull();
    $this->post(route('admin.settings.email.test'), ['to' => 'me@drinkwell.example'])->assertRedirect(route('admin.settings.email'));

    $this->post(route('admin.settings.email.code'), ['developer_code' => 'DW-EMAIL-0000'])->assertSessionHasErrors('developer_code');
    $this->get(route('admin.settings.email'))->assertSee('Developer code needed');

    $this->post(route('admin.settings.email.code'), ['developer_code' => ' dw-email-1453 '])->assertRedirect(route('admin.settings.email'));
    $this->get(route('admin.settings.email'))->assertSee('Mail server');
    expect(AdminAuditLog::where('action', 'email_settings_code_failed')->count())->toBe(1)
        ->and(AdminAuditLog::where('action', 'email_settings_opened')->count())->toBe(1);

    $this->travel(31)->minutes();
    $this->get(route('admin.settings.email'))->assertSee('Developer code needed');
});

it('accepts a plain user name, and then asks for the From address', function () use ($form) {
    $this->put(route('admin.settings.email.save'), $form(['username' => 'dwmailer', 'from_address' => '']))->assertSessionHasErrors('from_address');
    $this->put(route('admin.settings.email.save'), $form(['username' => 'dwmailer', 'from_address' => 'no-reply@drinkwell.example']))->assertSessionHasNoErrors();
    expect(config('mail.mailers.smtp.username'))->toBe('dwmailer')->and(config('mail.from.address'))->toBe('no-reply@drinkwell.example');
});
