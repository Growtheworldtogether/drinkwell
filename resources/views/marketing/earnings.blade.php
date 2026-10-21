@php
    $fmt = fn (int $c) => \App\Support\Money::format($c);
@endphp
<x-marketing-layout title="Your earnings">
    <section class="cc-pagehead" data-bg="coral">
        <div class="cc-wrap">
            <h1 class="cc-title1">Your earnings</h1>
            <p class="cc-lead">Every drink sells at {{ $fmt(config('drinkwell.unit_price_cents')) }}. Here is exactly how one day adds up, and how fast your deposit comes back.</p>
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="mint">
        <div class="cc-wrap">
            <p class="cc-statement" data-reveal>Your share is the pool, split in half.</p>
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="gray">
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

    <section class="cc-section" data-bg="cream">
        <div class="cc-wrap">
            <h2 class="cc-h2 cc-center" data-reveal>One day, step by step</h2>
            @php $c = $tiers[array_key_first($tiers)]; @endphp
            <ol class="cc-day">
                <li data-reveal><b>1</b><span>The machine sells all {{ $c['capacity'] }} drinks at {{ $fmt(config('drinkwell.unit_price_cents')) }}. That is {{ $fmt($c['gross']) }} in sales.</span></li>
                <li data-reveal><b>2</b><span>The bottling and logistics fee of {{ $fmt($c['fee']) }} comes off. {{ $fmt($c['pool']) }} is left in the pool.</span></li>
                <li data-reveal><b>3</b><span>The pool is split into two equal halves, so you receive {{ $fmt($c['share']) }} and your co-partner receives {{ $fmt($c['share']) }}.</span></li>
                <li data-reveal><b>4</b><span>Your {{ $fmt($c['share']) }} is added to your balance and listed in your ledger. This example uses the {{ $c['label'] }}.</span></li>
            </ol>
        </div>
    </section>

    <section class="cc-section" data-bg="gray">
        <div class="cc-wrap">
            <h2 class="cc-h2 cc-center" data-reveal>Getting your money out</h2>
            <div class="cc-tiles cc-tiles-4">
                <article class="cc-tile is-white" data-reveal><h3>1. Ask</h3><p>Open Withdraw on your dashboard and enter an amount of at least {{ $fmt(config('drinkwell.withdrawal_min_cents')) }}.</p></article>
                <article class="cc-tile is-white" data-reveal><h3>2. Confirm</h3><p>We email a six digit code to make sure it is really you. Type it in.</p></article>
                <article class="cc-tile is-white" data-reveal><h3>3. We review</h3><p>Our team looks at your request within {{ config('drinkwell.withdrawal_review_days') }} business days.</p></article>
                <article class="cc-tile is-white" data-reveal><h3>4. You are paid</h3><p>When it is approved you get an email with the payment reference.</p></article>
            </div>
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
</x-marketing-layout>
