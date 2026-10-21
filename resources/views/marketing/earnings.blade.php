@php
    $fmt = fn (int $c) => \App\Support\Money::format($c);
@endphp
<x-marketing-layout title="Your earnings">
    <section class="cc-pagehead" data-bg="coral">
        <div class="cc-wrap">
            <x-crumb>Your earnings</x-crumb>
            <h1 class="cc-title1">Your earnings</h1>
            <p class="cc-lead">Every drink sells at {{ $fmt(config('drinkwell.unit_price_cents')) }}. Here is exactly how one day adds up, and how fast your deposit comes back.</p>
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="mint">
        <div class="cc-wrap">
            <p class="cc-statement" data-reveal>Your share is the pool, split in half.</p>
        </div>
    </section>

    <section id="split" class="cc-section cc-center" data-bg="gray">
        <div class="cc-wrap">
            <h2 class="cc-h2" data-reveal>The daily split</h2>
            <p class="cc-lead" style="margin-inline:auto" data-reveal>Sales come in, the bottling and logistics fee comes off, and the rest is split equally between you and your co-partner.</p>
            <div class="cc-splits">
                @foreach ($tiers as $t)
                    <article class="cc-split" data-reveal aria-label="{{ $t['label'] }}">
                        <h3>{{ $t['label'] }}</h3>
                        <dl>
                            <div><dt>Drinks sold</dt><dd>{{ $t['capacity'] }}</dd></div>
                            <div><dt>Sales</dt><dd>{{ $fmt($t['gross']) }}</dd></div>
                            <div><dt>Bottling and logistics fee</dt><dd>- {{ $fmt($t['fee']) }}</dd></div>
                            <div><dt>Pool to split</dt><dd>{{ $fmt($t['pool']) }}</dd></div>
                            <div class="is-total"><dt>Your share (half)</dt><dd>{{ $fmt($t['share']) }}</dd></div>
                            <div><dt>Usual range</dt><dd>{{ $fmt($t['band_min']) }} to {{ $fmt($t['band_max']) }}</dd></div>
                        </dl>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="amber">
        <div class="cc-wrap">
            @php $c = $tiers[array_key_first($tiers)]; @endphp
            <p class="cc-statement" data-reveal>{{ $c['capacity'] }} drinks sell for {{ $fmt($c['gross']) }}. After the {{ $fmt($c['fee']) }} fee, {{ $fmt($c['pool']) }} is split in two, so you receive {{ $fmt($c['share']) }} every day.</p>
            <p class="cc-lead" style="margin-inline:auto" data-reveal>That is one day with the {{ $c['label'] }}. Your {{ $fmt($c['share']) }} is added to your balance and listed in your ledger.</p>
        </div>
    </section>

    <section id="withdraw" class="cc-section cc-center" data-bg="gray">
        <div class="cc-wrap">
            <h2 class="cc-huge" data-reveal><span>Getting your</span><span>money out</span></h2>
            <ol class="cc-cards3">
                <li class="cc-card cc-card-1" data-reveal><div class="cc-photo" aria-hidden="true"><span>Photo</span></div><div class="cc-card-body"><h3>Ask</h3><p>Open Withdraw on your dashboard and enter an amount of at least {{ $fmt(config('drinkwell.withdrawal_min_cents')) }}.</p></div></li>
                <li class="cc-card cc-card-2" data-reveal><div class="cc-photo" aria-hidden="true"><span>Photo</span></div><div class="cc-card-body"><h3>Confirm</h3><p>We email you a six digit code to make sure it is really you. Type it in.</p></div></li>
                <li class="cc-card cc-card-3" data-reveal><div class="cc-photo" aria-hidden="true"><span>Photo</span></div><div class="cc-card-body"><h3>Get paid</h3><p>Our team reviews it within {{ config('drinkwell.withdrawal_review_days') }} business days, and you get an email with the payment reference.</p></div></li>
            </ol>
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="gray">
        <div class="cc-wrap">
            <h2 class="cc-h2" data-reveal>Two machines to choose from</h2>
            @include('marketing.partials.options')
        </div>
    </section>

    <section id="calculator" class="cc-section cc-center" data-bg="gray">
        <div class="cc-wrap" x-data="yieldCalculator(@js($calc))">
            <h2 class="cc-h2" data-reveal>Yield calculator</h2>
            <p class="cc-lead" style="margin-inline:auto" data-reveal>Pick a machine and see how fast the deposit comes back at the usual daily share.</p>
            @include('marketing.partials.calculator')
        </div>
    </section>
    <x-related :links="[['program', 'How it works'], ['locations', 'Where machines stand']]" />
</x-marketing-layout>
