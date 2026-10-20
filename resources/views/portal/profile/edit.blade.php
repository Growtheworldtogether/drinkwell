<x-app-layout title="Profile">
    <section class="cc-screen" data-bg="cream">
        <div class="cc-wrap" style="max-width:900px">
            <h1 class="cc-title1" style="font-size:clamp(40px,7vw,96px);margin-bottom:28px">Your profile</h1>
            @if (is_string(session('status')) && ! in_array(session('status'), ['profile-updated', 'password-updated'], true))
                <x-cc.notice>{{ session('status') }}</x-cc.notice>
            @endif

            <div class="cc-panel">
                <h2>Your details</h2>
                @include('portal.profile.partials.update-profile-information-form')
            </div>

            @if ($payout ?? null)
                <div class="cc-panel">
                    <h2>Payout account</h2>
                    <dl class="cc-dl">
                        <div class="cc-kv"><dt>Bank</dt><dd>{{ $payout->bank_name }}</dd></div>
                        <div class="cc-kv"><dt>Account holder</dt><dd>{{ $payout->holder_name }}</dd></div>
                        <div class="cc-kv"><dt>Account</dt><dd>Ending {{ $payout->last4 }}</dd></div>
                    </dl>
                    <p class="cc-hint" style="margin-top:12px">Only the last four digits are ever shown.</p>
                </div>
            @endif

            <div class="cc-panel">
                <h2>Change your password</h2>
                @include('portal.profile.partials.update-password-form')
            </div>

            <div class="cc-panel cc-danger">
                <h2>Delete your account</h2>
                @include('portal.profile.partials.delete-user-form')
            </div>
        </div>
    </section>
</x-app-layout>
