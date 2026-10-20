<x-auth-page title="Welcome back" intro="Sign in to see your machine and your payouts." bg="coral">
    <form method="POST" action="{{ route('login') }}" class="cc-form">
        @csrf

        <x-cc.field name="email" label="Email" type="email" :value="old('email')" required autofocus autocomplete="username" placeholder="you@example.com" />
        <x-cc.field name="password" label="Password" type="password" required autocomplete="current-password" />
        <x-cc.check name="remember" label="Keep me signed in on this device" />

        <button type="submit" class="af-btn af-btn-black">Sign in</button>
        <div class="cc-links">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}">Forgot your password?</a>
            @endif
            <a href="{{ route('register') }}">Create an account</a>
        </div>
    </form>
</x-auth-page>
