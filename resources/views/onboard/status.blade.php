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

            @if (! $mine && ($searching ?? false))
                @include('onboard._searching', ['tier' => $tier])
            @elseif (! $mine)
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
                @if (session('just_paired'))
                    @include('onboard._found-style')
                @endif
                <x-cc.head :title="session('just_paired') ? 'We found your co-partner' : ($pending ? 'You are paired' : 'You are all set')" eyebrow="Your co-partner" :lead="$partner->name.($partner->profile?->city ? ' in '.$partner->profile->city : '').' is your co-partner.'">
                    @unless ($pending)<a class="af-btn af-btn-black" href="{{ route('hub.dashboard') }}">Open the dashboard</a>@endunless
                </x-cc.head>
                @include('onboard._progress', ['stage' => $stage])
                @if (session('just_paired'))
                    <div class="cc-panel dw-found-card dw-in" style="--d:0">
                        <div class="dw-found-who">
                            <span class="cc-avatar" aria-hidden="true">{{ \Illuminate\Support\Str::of($partner->name)->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('') }}</span>
                            <div>
                                <p class="cc-eyebrow" style="margin:0">Your co-partner</p>
                                <p class="dw-found-name">{{ $partner->name }}</p>
                                @if ($partner->profile?->city)<p class="cc-hint" style="margin:0">{{ $partner->profile->city }}</p>@endif
                            </div>
                        </div>
                        <p style="margin:0">You each own half of the machine below and split what it earns every day. Your deposit details are further down, and we have emailed them to you too.</p>
                    </div>
                @endif
                <div class="cc-panel {{ session('just_paired') ? 'dw-in dw-rows' : '' }}" style="--d:1">
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
