<form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

<form method="post" action="{{ route('profile.update') }}" class="cc-form">
    @csrf
    @method('patch')

    <div class="cc-two">
        <x-cc.field name="name" label="Full name" :value="old('name', $user->name)" required autocomplete="name" />
        <div>
            <x-cc.field name="email" label="Email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
            @if ($user->email_verified_at === null)
                <p class="cc-hint">Your email address is unverified. <button form="send-verification" class="cc-edit" style="float:none">Send a verification code.</button></p>
            @endif
        </div>
    </div>

    <button type="submit" class="af-btn af-btn-black" style="width:auto">Save details</button>
    @if (session('status') === 'profile-updated')
        <p x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" class="cc-notice" role="status">Saved.</p>
    @endif
</form>
