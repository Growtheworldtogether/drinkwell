<x-app-layout title="Profile" bg="cream">
    <div>
        <x-cc.head title="Your profile" eyebrow="Your account" lead="Your details, your payout account and your password." />
            @if (is_string(session('status')) && ! in_array(session('status'), ['profile-updated', 'password-updated'], true))
                <x-cc.notice>{{ session('status') }}</x-cc.notice>
            @endif

            <div class="cc-grid is-two">
            <div>
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
            </div>

            <div class="cc-panel">
                <h2>Change your password</h2>
                @include('portal.profile.partials.update-password-form')
            </div>
            </div>

            <div class="cc-panel cc-danger">
                <h2>Delete your account</h2>
                @include('portal.profile.partials.delete-user-form')
            </div>
    </div>
</x-app-layout>
