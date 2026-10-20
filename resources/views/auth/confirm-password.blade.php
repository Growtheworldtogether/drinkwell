<x-auth-page title="Confirm your password" intro="This area needs your password again before you continue.">
    <form method="POST" action="{{ route('password.confirm') }}" class="flex flex-col gap-4">
        @csrf

        <x-field name="password" label="Password" type="password" required autocomplete="current-password" />

        <x-button type="submit" class="w-full sm:w-auto sm:self-end">Confirm</x-button>
    </form>
</x-auth-page>
