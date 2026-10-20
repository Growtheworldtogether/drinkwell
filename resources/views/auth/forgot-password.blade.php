<x-auth-page title="Reset your password" intro="Enter your email address and we will send you a six digit code. You use the code on the next page to choose a new password.">
    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4">
        @csrf
        <x-form.guard />

        <x-field name="email" label="Email" type="email" :value="old('email')" required autofocus autocomplete="username" />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('password.reset') }}" class="flex min-h-11 items-center hover:underline">I already have a code</a>
            <x-button type="submit" class="w-full sm:w-auto">Send code</x-button>
        </div>
    </form>
</x-auth-page>
