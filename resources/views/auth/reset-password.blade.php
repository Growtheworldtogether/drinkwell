<x-auth-page title="Choose a new password" intro="Enter the six digit code from the email and a new password of at least 10 characters with letters and numbers.">
    <form method="POST" action="{{ route('password.store') }}" class="flex flex-col gap-4">
        @csrf

        <x-field name="email" label="Email" type="email" :value="$email" required autocomplete="username" />
        <x-otp-input name="code" label="Code" />
        <x-field name="password" label="New password" type="password" required autocomplete="new-password" />
        <x-field name="password_confirmation" label="Confirm password" type="password" required autocomplete="new-password" />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('password.request') }}" class="flex min-h-11 items-center hover:underline">Send a new code</a>
            <x-button type="submit" class="w-full sm:w-auto">Change password</x-button>
        </div>
    </form>
</x-auth-page>
