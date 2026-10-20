<x-auth-page title="Create your account">
    <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-4">
        @csrf
        <x-form.guard />

        <x-field name="name" label="Name" :value="old('name')" required autofocus autocomplete="name" />
        <x-field name="email" label="Email" type="email" :value="old('email')" required autocomplete="username" />
        <x-field name="password" label="Password" type="password" required autocomplete="new-password" />
        <x-field name="password_confirmation" label="Confirm password" type="password" required autocomplete="new-password" />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('login') }}" class="flex min-h-11 items-center hover:underline">Already registered?</a>
            <x-button type="submit" class="w-full sm:w-auto">Register</x-button>
        </div>
    </form>
</x-auth-page>
