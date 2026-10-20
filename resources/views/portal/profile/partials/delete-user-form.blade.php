<p>This removes your account and its data for good. You will be asked for your password first.</p>

<form method="post" action="{{ route('profile.destroy') }}" class="cc-form" x-data="{ open: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
    @csrf
    @method('delete')

    <button type="button" class="af-btn cc-bad" style="width:auto" x-show="! open" x-on:click="open = true">Delete account</button>

    <div x-show="open" x-cloak class="cc-form">
        <x-cc.field name="password" label="Your password" type="password" id="delete_password" help="Enter your password to confirm." :error="$errors->userDeletion->first('password') ?: null" autocomplete="current-password" />
        <div class="cc-step-nav">
            <button type="submit" class="af-btn cc-bad" style="width:auto">Yes, delete my account</button>
            <button type="button" class="af-btn cc-btn-outline" style="width:auto" x-on:click="open = false">Cancel</button>
        </div>
    </div>
</form>
