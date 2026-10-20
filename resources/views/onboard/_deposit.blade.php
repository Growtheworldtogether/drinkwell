@php $fmt = fn (int $c) => \App\Support\Money::format($c); @endphp
<section class="pf-panel" aria-labelledby="dep-title">
    <div class="pf-panel-head"><p class="pf-label" id="dep-title">Send your deposit to {{ $partner->name }}</p></div>
    <p class="pf-text">You are buying your half of a machine {{ $partner->name }} already owns, so the deposit goes straight to them and never to {{ config("app.name") }}. Your share starts the day they confirm it.</p>
    @if ($req && $req->isPaired())
        <dl class="pf-rows">
            <div><dt>Account holder</dt><dd>{{ $req->account_holder }}</dd></div>
            <div><dt>Bank</dt><dd>{{ $req->bank_name }}</dd></div>
            @if ($req->routing_number)<div><dt>Routing number</dt><dd class="pf-mono">{{ $req->routing_number }}</dd></div>@endif
            <div><dt>Account number</dt><dd class="pf-mono">{{ $req->account_number }}</dd></div>
            <div><dt>Amount</dt><dd class="pf-mono">{{ $fmt($mine->capital_cents) }}</dd></div>
            <div><dt>Reference</dt><dd class="pf-mono">{{ $reference }}</dd></div>
            @if ($req->note)<div><dt>Note</dt><dd>{{ $req->note }}</dd></div>@endif
        </dl>
        <div class="pf-actions">
            @if ($req->deposit_reported_at)
                <span class="pf-note">You said you sent it on {{ $req->deposit_reported_at->format('M j, g:i a') }}. We will tell you when {{ $partner->name }} confirms.</span>
            @else
                <form method="POST" action="{{ route('onboard.report') }}">@csrf<button type="submit" class="pf-btn">I sent my deposit</button></form>
            @endif
        </div>
    @endif
</section>
