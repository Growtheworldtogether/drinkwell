@php
    $fmt = fn (int $c) => \App\Support\Money::format($c);
    $tierLabel = \App\Enums\Tier::from($req->tier)->label();
    $pending = $mine && $mine->settlement_status === \App\Enums\SettlementStatus::PendingSettlement;
    $stage = ! $mine ? 1 : (! $pending ? 4 : ($req?->deposit_reported_at ? 3 : 2));
@endphp
<x-app-layout title="Your partner and deposit">
    <div class="pf-page">
        @if (session('status'))
            <p class="pf-notice" role="status">{{ session('status') }}</p>
        @endif

        @include('onboard._progress', ['stage' => $stage])

        @if (! $mine)
            <section class="pf-panel" aria-labelledby="st-find">
                <div class="pf-panel-head"><p class="pf-label" id="st-find">Finding a partner</p></div>
                <p class="pf-text">We are looking for a co-partner for your {{ $tierLabel }}. This usually takes <strong>1 business day</strong>. We will email you as soon as you are paired, and the details will appear on this page.</p>
                <dl class="pf-rows">
                    <div><dt>Option</dt><dd>{{ $tierLabel }}</dd></div>
                    <div><dt>Your deposit</dt><dd class="pf-mono">{{ $fmt(\App\Enums\Tier::from($req->tier)->stakeCents()) }}</dd></div>
                    <div><dt>Requested</dt><dd>{{ $req->created_at->format('M j, g:i a') }}</dd></div>
                </dl>
            </section>
        @else
            <section class="pf-panel" aria-labelledby="st-machine">
                <div class="pf-panel-head"><p class="pf-label" id="st-machine">Your machine</p></div>
                <dl class="pf-rows">
                    <div><dt>Serial number</dt><dd class="pf-mono">{{ $machine->serial_no }}</dd></div>
                    <div><dt>Facility</dt><dd>{{ $machine->facility_name }}, {{ $machine->city }}</dd></div>
                    <div><dt>Option</dt><dd>{{ $machine->tier->label() }}</dd></div>
                    <div><dt>Your deposit</dt><dd class="pf-mono">{{ $fmt($mine->capital_cents) }}</dd></div>
                    <div><dt>Status</dt><dd><x-status-tag :status="$mine->settlement_status->value" :label="$pending ? 'Waiting for '.$partner->name.' to confirm' : 'Confirmed'" :tone="$pending ? 'warn' : 'ok'" /></dd></div>
                </dl>
            </section>

            <section class="pf-panel" aria-labelledby="st-partner">
                <div class="pf-panel-head"><p class="pf-label" id="st-partner">Your co-partner</p></div>
                <dl class="pf-rows">
                    <div><dt>Name</dt><dd>{{ $partner->name }}</dd></div>
                    @if ($partner->profile?->city)<div><dt>City</dt><dd>{{ $partner->profile->city }}</dd></div>@endif
                </dl>
            </section>

            @if ($pending)
                @include('onboard._deposit')
            @else
                <p class="pf-text">Your deposit is confirmed. <a class="pf-link" href="{{ route('hub.dashboard') }}">Open the dashboard</a>.</p>
            @endif
        @endif
    </div>
</x-app-layout>
