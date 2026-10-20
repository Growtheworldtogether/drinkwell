<x-auth-page title="Sign in">
    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
        @csrf

        <x-field name="email" label="Email" type="email" :value="old('email')" required autofocus autocomplete="username" />
        <x-field name="password" label="Password" type="password" required autocomplete="current-password" />
        <x-field name="remember" label="Remember me" type="checkbox" />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="flex min-h-11 items-center hover:underline">Forgot your password?</a>
            @endif
            <x-button type="submit" class="w-full sm:w-auto">Sign in</x-button>
        </div>
    </form>
</x-auth-page>
