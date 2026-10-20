<form method="post" action="{{ route('password.update') }}" class="cc-form">
    @csrf
    @method('put')

    <x-cc.field name="current_password" label="Current password" type="password" id="update_password_current_password" :error="$errors->updatePassword->first('current_password') ?: null" autocomplete="current-password" />
    <div class="cc-two">
        <x-cc.field name="password" label="New password" type="password" id="update_password_password" :error="$errors->updatePassword->first('password') ?: null" autocomplete="new-password" help="At least 10 characters, with letters and numbers." />
        <x-cc.field name="password_confirmation" label="Confirm new password" type="password" id="update_password_password_confirmation" :error="$errors->updatePassword->first('password_confirmation') ?: null" autocomplete="new-password" />
    </div>

    <button type="submit" class="af-btn af-btn-black" style="width:auto">Update password</button>
    @if (session('status') === 'password-updated')
        <p x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" class="cc-notice" role="status">Saved.</p>
    @endif
</form>
