<x-auth-page title="Choose a new password" intro="Enter the six-digit code from the email and a new password of at least 10 characters with letters and numbers." bg="amber">
    <form method="POST" action="{{ route('password.store') }}" class="cc-form">
        @csrf

        <x-cc.field name="email" label="Email" type="email" :value="$email" required autocomplete="username" />
        <x-cc.otp name="code" label="Your code" />
        <x-cc.field name="password" label="New password" type="password" required autocomplete="new-password" />
        <x-cc.field name="password_confirmation" label="Confirm password" type="password" required autocomplete="new-password" />

        <button type="submit" class="af-btn af-btn-black">Change password</button>
        <div class="cc-links"><a href="{{ route('password.request') }}">Send a new code</a></div>
    </form>
</x-auth-page>
