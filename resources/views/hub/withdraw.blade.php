@php $fmt = fn (int $c) => \App\Support\Money::format($c); @endphp
<x-app-layout title="Withdraw">
    <section class="cc-screen" data-bg="gray" style="padding-top:120px">
        <div class="cc-wrap" style="max-width:1000px">
            <a class="cc-back" href="{{ route('hub.dashboard') }}">Back to dashboard</a>
            @if (session('status'))
                <x-cc.notice>{{ session('status') }}</x-cc.notice>
            @endif
            <h1 class="cc-title1" style="font-size:clamp(40px,6vw,88px);margin-bottom:28px">Withdraw</h1>

            <div class="cc-tiles cc-tiles-3">
                <div class="cc-tile"><small>Available now</small><b>{{ $fmt($available) }}</b><span>ready to withdraw</span></div>
                <div class="cc-tile"><small>Waiting for approval</small><b>{{ $fmt($waiting) }}</b><span>being reviewed</span></div>
                <div class="cc-tile"><small>Paid out</small><b>{{ $fmt($paidOut) }}</b><span>sent to your bank</span></div>
            </div>

            @if ($awaiting)
                <div class="cc-panel">
                    <h2>Enter your code</h2>
                    <p>To take out <strong>{{ $fmt($awaiting->amount_cents) }}</strong>, enter the six digit code we emailed to <strong style="overflow-wrap:anywhere">{{ $maskedEmail }}</strong>. It works for 10 minutes.</p>
                    <form method="POST" action="{{ route('hub.withdraw.confirm', $awaiting) }}" class="cc-form">
                        @csrf
                        <x-cc.otp name="code" label="Your code" />
                        <div class="cc-step-nav">
                            <button type="submit" class="af-btn af-btn-black">Confirm withdrawal</button>
                        </div>
                    </form>
                    <div class="cc-links" style="margin-top:16px">
                        <form method="POST" action="{{ route('hub.withdraw.resend', $awaiting) }}">@csrf<button type="submit" class="cc-edit" style="float:none">Send a new code</button></form>
                        <form method="POST" action="{{ route('hub.withdraw.cancel', $awaiting) }}">@csrf @method('DELETE')<button type="submit" class="cc-edit" style="float:none">Cancel this request</button></form>
                    </div>
                </div>
            @elseif (! $enabled)
                <x-cc.notice>Withdrawals are paused for now. Please check back soon.</x-cc.notice>
            @elseif (! $account)
                <x-cc.notice type="error">There is no payout account on file yet. Please contact support.</x-cc.notice>
            @else
                <div class="cc-panel">
                    <h2>Ask for a withdrawal</h2>
                    <p>The money goes to <strong>{{ $account->bank_name }}</strong>, account ending <strong>{{ $account->last4 }}</strong> ({{ $account->holder_name }}). The smallest withdrawal is {{ $fmt($min) }}. We review each request within {{ $reviewDays }} business days.</p>
                    <form method="POST" action="{{ route('hub.withdraw.request') }}" class="cc-form" x-data="{ amount: @js(old('amount', '')) }">
                        @csrf
                        <x-cc.field name="amount" label="Amount in dollars" inputmode="decimal" autocomplete="off" placeholder="{{ number_format($min / 100, 2) }}" x-model="amount" :help="'You can take out up to '.$fmt($available).'.'" />
                        <div class="cc-step-nav">
                            <button type="submit" class="af-btn af-btn-black" @disabled($available < $min)>Send me a code</button>
                            <button type="button" class="af-btn cc-btn-outline" x-on:click="amount = '{{ number_format($available / 100, 2, '.', '') }}'" @disabled($available < $min)>Withdraw everything</button>
                        </div>
                        @if ($available < $min)
                            <p class="cc-hint">You need at least {{ $fmt($min) }} available. Payouts add up every day.</p>
                        @endif
                    </form>
                </div>
            @endif

            <div class="cc-panel">
                <h2>Your withdrawals</h2>
                @forelse ($history as $w)
                    <div class="cc-wrow">
                        <div class="cc-wmain">
                            <strong>{{ $fmt($w->amount_cents) }}</strong>
                            <span class="cc-tone {{ $w->status === \App\Enums\WithdrawalStatus::Approved ? 'ok' : '' }}">{{ $w->status->label() }}</span>
                        </div>
                        <p class="cc-hint">Asked {{ $w->created_at->format('M j, Y') }} · {{ $w->bank_name }} ending {{ $w->account_last4 }} · {{ $w->reference }}</p>
                        @if ($w->status === \App\Enums\WithdrawalStatus::Approved)
                            <p>Payment reference <strong>{{ $w->paid_reference }}</strong></p>
                        @elseif ($w->status === \App\Enums\WithdrawalStatus::Rejected)
                            <p>Reason: {{ $w->admin_note }}</p>
                        @elseif ($w->status === \App\Enums\WithdrawalStatus::Pending)
                            <form method="POST" action="{{ route('hub.withdraw.cancel', $w) }}">@csrf @method('DELETE')<button type="submit" class="cc-edit" style="float:none">Cancel this request</button></form>
                        @endif
                    </div>
                @empty
                    <p>No withdrawals yet. When you ask for one, it shows up here.</p>
                @endforelse
            </div>
        </div>
    </section>
</x-app-layout>
