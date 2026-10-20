<x-auth-page title="Confirm your password" intro="This area needs your password again before you continue." bg="cream">
    <form method="POST" action="{{ route('password.confirm') }}" class="cc-form">
        @csrf

        <x-cc.field name="password" label="Password" type="password" required autocomplete="current-password" />

        <button type="submit" class="af-btn af-btn-black">Confirm</button>
    </form>
</x-auth-page>
