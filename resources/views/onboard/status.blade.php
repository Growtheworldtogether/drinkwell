@php $fmt = fn (int $c) => \App\Support\Money::format($c); $pending = $mine->settlement_status === \App\Enums\SettlementStatus::PendingSettlement; @endphp
<x-app-layout title="Pairing and deposit">
    <div class="pf-page">
        @if (session('status'))
            <p class="pf-notice" role="status">{{ session('status') }}</p>
        @endif

        <p class="dw-demo">Demo: your co-partner and every amount are fictional. No real money moves.</p>

        <section class="pf-panel" aria-labelledby="st-machine">
            <div class="pf-panel-head"><p class="pf-label" id="st-machine">Your machine</p></div>
            <dl class="pf-rows">
                <div><dt>Serial number</dt><dd class="pf-mono">{{ $machine->serial_no }}</dd></div>
                <div><dt>Facility</dt><dd>{{ $machine->facility_name }}, {{ $machine->city }}</dd></div>
                <div><dt>Option</dt><dd>{{ $machine->tier->label() }}</dd></div>
                <div><dt>Your deposit</dt><dd class="pf-mono">{{ $fmt($mine->capital_cents) }}</dd></div>
                <div><dt>Ownership</dt><dd><x-status-tag :status="$mine->settlement_status->value" :label="$mine->settlement_status->label()" :tone="$pending ? 'warn' : 'ok'" /></dd></div>
            </dl>
        </section>

        <section class="pf-panel" aria-labelledby="st-partner">
            <div class="pf-panel-head"><p class="pf-label" id="st-partner">Your co-partner (simulated)</p></div>
            <dl class="pf-rows">
                <div><dt>Name</dt><dd>{{ $partner->name }}</dd></div>
                <div><dt>Email</dt><dd class="pf-mono">{{ $partner->email }}</dd></div>
                <div><dt>Phone</dt><dd class="pf-mono">{{ $partner->profile?->phone }}</dd></div>
            </dl>
        </section>

        @if ($pending)
            <section class="pf-panel" aria-labelledby="st-pay">
                <div class="pf-panel-head"><p class="pf-label" id="st-pay">Settle your deposit</p></div>
                <p class="pf-text">This platform takes no card or online payment. In the story, each partner sends their deposit by bank wire to the escrow account, quoting the reference below. We emailed you these steps and a message you can send to your co-partner.</p>
                <dl class="pf-rows">
                    <div><dt>Escrow reference</dt><dd class="pf-mono">{{ $reference }}</dd></div>
                    <div><dt>Amount</dt><dd class="pf-mono">{{ $fmt($mine->capital_cents) }}</dd></div>
                </dl>
                <div class="pf-actions">
                    @unless ($reported)
                        <form method="POST" action="{{ route('onboard.report') }}">@csrf<button type="submit" class="pf-btn">I sent my deposit</button></form>
                    @else
                        <span class="pf-note">Reported. An administrator will confirm it.</span>
                    @endunless
                    <form method="POST" action="{{ route('onboard.demo-confirm') }}">@csrf<button type="submit" class="pf-btn">Demo: confirm it now</button></form>
                </div>
            </section>
        @else
            <p class="pf-text">Your deposit is confirmed. <a class="pf-link" href="{{ route('hub.dashboard') }}">Open the dashboard</a>.</p>
        @endif
    </div>
</x-app-layout>
