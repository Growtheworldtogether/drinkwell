<x-auth-page title="Verify your email" intro="Enter the six digit code we emailed to confirm your address. The code expires in 10 minutes.">
    <p class="mb-6 break-anywhere font-mono tabular-nums text-af-700">{{ $email }}</p>

    @if ($errors->has('mail'))
        <x-banner type="error" class="mb-6">{{ $errors->first('mail') }}</x-banner>
    @endif

    <form method="POST" action="{{ route('verification.verify') }}" class="flex flex-col gap-4">
        @csrf

        <x-otp-input name="code" label="Verification code" />

        <x-button type="submit" class="w-full sm:w-auto sm:self-end">Verify email</x-button>
    </form>

    <div class="mt-8 flex flex-col gap-4 border-t border-af-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-button type="submit" variant="secondary" class="w-full sm:w-auto">Send a new code</x-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-button type="submit" variant="tertiary" class="w-full sm:w-auto">Log out</x-button>
        </form>
    </div>
</x-auth-page>
