<p class="mb-4 text-af-500">Use a long, random password to keep your account secure.</p>

<form method="post" action="{{ route('password.update') }}" class="flex flex-col gap-4">
    @csrf
    @method('put')

    <x-field name="current_password" label="Current password" type="password" id="update_password_current_password" :error="$errors->updatePassword->first('current_password') ?: null" autocomplete="current-password" />
    <x-field name="password" label="New password" type="password" id="update_password_password" :error="$errors->updatePassword->first('password') ?: null" autocomplete="new-password" />
    <x-field name="password_confirmation" label="Confirm password" type="password" id="update_password_password_confirmation" :error="$errors->updatePassword->first('password_confirmation') ?: null" autocomplete="new-password" />

    <div class="flex items-center gap-4">
        <x-button type="submit" variant="secondary">Save</x-button>

        @if (session('status') === 'password-updated')
            <p x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 2000)" class="text-af-500">Saved.</p>
        @endif
    </div>
</form>
