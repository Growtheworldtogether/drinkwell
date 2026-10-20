<x-auth-page title="Check your email" intro="We sent a six-digit code to the address below. It works for 10 minutes." bg="cream">
    <p class="cc-sub" style="margin-top:-12px;font-weight:700;overflow-wrap:anywhere">{{ $email }}</p>

    @if ($errors->has('mail'))
        <x-cc.notice type="error">{{ $errors->first('mail') }}</x-cc.notice>
    @endif

    <form method="POST" action="{{ route('verification.verify') }}" class="cc-form">
        @csrf

        <x-cc.otp name="code" label="Your code" />

        <button type="submit" class="af-btn af-btn-black">Verify</button>
    </form>

    <div class="cc-links" style="margin-top:20px">
        <form method="POST" action="{{ route('verification.send') }}">@csrf<button type="submit" class="cc-edit" style="float:none">Send a new code</button></form>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="cc-edit" style="float:none">Use another email</button></form>
    </div>
</x-auth-page>
