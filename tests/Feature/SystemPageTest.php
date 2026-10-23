<?php

use App\Enums\Role;
use App\Models\AdminAuditLog;

it('shows site health and installs updates for an administrator', function () {
    signedIn(Role::Admin);
    session(['auth.password_confirmed_at' => time()]);

    $this->get(route('admin.settings.system'))->assertOk()->assertSee('Site health')->assertSee('The database is up to date');

    $this->from(route('admin.settings.system'))->post(route('admin.settings.system.update'))
        ->assertRedirect(route('admin.settings.system'))->assertSessionHas('status');
    expect(AdminAuditLog::where('action', 'system.update')->exists())->toBeTrue();
});

it('keeps members out of the system page', function () {
    signedIn(Role::Partner);
    $this->get(route('admin.settings.system'))->assertStatus(403);
});
