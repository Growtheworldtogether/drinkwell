<p class="mb-4 text-af-500">Update your name and email address.</p>

<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}" class="flex flex-col gap-4">
    @csrf
    @method('patch')

    <x-field name="name" label="Name" :value="old('name', $user->name)" required autocomplete="name" />

    <div>
        <x-field name="email" label="Email" type="email" :value="old('email', $user->email)" required autocomplete="username" />

        @if ($user->email_verified_at === null)
            <p class="mt-2">
                Your email address is unverified.
                <button form="send-verification" class="inline min-h-11 text-af-accent hover:underline lg:min-h-0">Send a verification code.</button>
            </p>
        @endif
    </div>

    <div class="flex items-center gap-4">
        <x-button type="submit">Save</x-button>

        @if (session('status') === 'profile-updated')
            <p x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 2000)" class="text-af-500">Saved.</p>
        @endif
    </div>
</form>
