@php
    $fmt = fn (int $c) => \App\Support\Money::format($c);
    $first = $partner->name;
    $sent = $req && $req->deposit_reported_at;
@endphp
<div class="cc-grid is-split">
@if (\App\Support\DepositStyle::isContact())
<div class="cc-panel" id="deposit">
    <h2>Contact {{ $first }}</h2>
    <p>You are buying half of a machine {{ $first }} already owns, so the deposit goes straight to them and never to {{ app(\App\Services\BrandService::class)->name() }}. Your share starts the day they confirm it.</p>
    @if ($req && $req->isPaired())
        <dl class="cc-dl">
            <div class="cc-kv"><dt>Co-partner</dt><dd>{{ $req->partner_name ?: $partner->name }}</dd></div>
            @if ($req->partner_city)<div class="cc-kv"><dt>Location</dt><dd>{{ $req->partner_city }}</dd></div>@endif
            @if ($req->partner_email)<div class="cc-kv"><dt>Email</dt><dd><a href="mailto:{{ $req->partner_email }}" style="text-decoration:underline">{{ $req->partner_email }}</a></dd></div>@endif
            <div class="cc-kv"><dt>Amount</dt><dd>{{ $fmt($mine->capital_cents) }}</dd></div>
        </dl>
        <a href="{{ route('onboard.steps', 1) }}" class="af-btn af-btn-black" style="width:auto">See the payment steps again</a>
    @endif
</div>
@else
<div class="cc-panel" id="deposit">
    <h2>Send your deposit to {{ $first }}</h2>
    <p>You are buying half of a machine {{ $first }} already owns, so the deposit goes straight to them and never to {{ app(\App\Services\BrandService::class)->name() }}. Your share starts the day they confirm it.</p>
    @if ($req && $req->isPaired())
        <dl class="cc-dl">
            <div class="cc-kv"><dt>Account holder</dt><dd>{{ $req->account_holder }}</dd></div>
            <div class="cc-kv"><dt>Bank</dt><dd>{{ $req->bank_name }}</dd></div>
            @if ($req->routing_number)<div class="cc-kv"><dt>Routing number</dt><dd>{{ $req->routing_number }}</dd></div>@endif
            <div class="cc-kv"><dt>Account number</dt><dd>{{ $req->account_number }}</dd></div>
            <div class="cc-kv"><dt>Amount</dt><dd>{{ $fmt($mine->capital_cents) }}</dd></div>
            <div class="cc-kv"><dt>Reference</dt><dd>{{ $reference }}</dd></div>
            @if ($req->note)<div class="cc-kv"><dt>Note</dt><dd>{{ $req->note }}</dd></div>@endif
        </dl>
    @endif
</div>

@endif

@include('onboard._proof')
</div>
