@php
    $fmt = fn (int $c) => \App\Support\Money::format($c);
    $tier = $req ? \App\Enums\Tier::from($req->tier) : ($machine?->tier ?? \App\Enums\Tier::Commercial);
    $pending = $mine && $mine->settlement_status === \App\Enums\SettlementStatus::PendingSettlement;
    $stage = ! $mine ? 1 : (! $pending ? 4 : ($req?->deposit_reported_at ? 3 : 2));
    $bg = ! $mine ? 'amber' : 'mint';
@endphp
<x-app-layout title="Partner and deposit" :bg="$bg">
    <div>
            @if (session('status'))
                <x-cc.notice>{{ session('status') }}</x-cc.notice>
            @endif

            @if (! $mine)
                <x-cc.head title="Finding a partner" eyebrow="Your request" :lead="'We are looking for a co-partner for your '.$tier->label().'. This usually takes 1 business day. We will email you as soon as you are paired.'" />
                @include('onboard._progress', ['stage' => 1])
                <div class="cc-panel">
                    <h2>Your request</h2>
                    <dl class="cc-dl">
                        <div class="cc-kv"><dt>Option</dt><dd>{{ $tier->label() }}</dd></div>
                        <div class="cc-kv"><dt>Your deposit</dt><dd>{{ $fmt($tier->stakeCents()) }}</dd></div>
                        <div class="cc-kv"><dt>Requested</dt><dd>{{ $req->created_at->format('M j, g:i a') }}</dd></div>
                        <div class="cc-kv"><dt>Next</dt><dd>We email you as soon as you are paired</dd></div>
                    </dl>
                </div>
            @else
                <x-cc.head :title="$pending ? 'You are paired' : 'You are all set'" eyebrow="Your co-partner" :lead="$partner->name.($partner->profile?->city ? ' in '.$partner->profile->city : '').' is your co-partner.'">
                    @unless ($pending)<a class="af-btn af-btn-black" href="{{ route('hub.dashboard') }}">Open the dashboard</a>@endunless
                </x-cc.head>
                @include('onboard._progress', ['stage' => $stage])
                <div class="cc-panel">
                    <h2>Your machine</h2>
                    <dl class="cc-dl">
                        <div class="cc-kv"><dt>Serial number</dt><dd>{{ $machine->serial_no }}</dd></div>
                        <div class="cc-kv"><dt>Facility</dt><dd>{{ $machine->facility_name }}, {{ $machine->city }}</dd></div>
                        <div class="cc-kv"><dt>Option</dt><dd>{{ $machine->tier->label() }}</dd></div>
                        <div class="cc-kv"><dt>Your deposit</dt><dd>{{ $fmt($mine->capital_cents) }}</dd></div>
                        <div class="cc-kv"><dt>Status</dt><dd><span class="cc-tone {{ $pending ? '' : 'ok' }}">{{ $pending ? ($req?->deposit_reported_at ? 'Proof sent, waiting for '.$partner->name.' to confirm' : 'Waiting for '.$partner->name.' to confirm') : 'Confirmed' }}</span></dd></div>
                    </dl>
                </div>
                @if ($pending)
                    @include('onboard._deposit')
                @else
                @endif
            @endif
    </div>
</x-app-layout>
