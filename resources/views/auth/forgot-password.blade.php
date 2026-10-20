<x-auth-page title="Forgot your password?" intro="Enter your email and we will send you a six-digit code." bg="amber">
    <form method="POST" action="{{ route('password.email') }}" class="cc-form">
        @csrf
        <x-form.guard />

        <x-cc.field name="email" label="Email" type="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@example.com" />

        <button type="submit" class="af-btn af-btn-black">Send me a code</button>
        <div class="cc-links"><a href="{{ route('password.reset') }}">I already have a code</a><a href="{{ route('login') }}">Back to sign in</a></div>
    </form>
</x-auth-page>
