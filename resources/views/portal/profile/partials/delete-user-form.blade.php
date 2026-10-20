<p class="mb-4 text-af-500">Deleting your account removes it and its data permanently. Download anything you want to keep first.</p>

<form method="post" action="{{ route('profile.destroy') }}">
    @csrf
    @method('delete')

    <x-inline-confirm trigger="Delete account" title="Delete your account?" confirm="Delete account" :open="$errors->userDeletion->isNotEmpty()">
        <x-field name="password" label="Password" type="password" id="delete_password" help="Enter your password to confirm." :error="$errors->userDeletion->first('password') ?: null" autocomplete="current-password" />
    </x-inline-confirm>
</form>
