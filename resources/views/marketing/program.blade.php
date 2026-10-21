@php
    $fmt = fn (int $c) => \App\Support\Money::format($c);
@endphp
<x-marketing-layout title="How it works">
    <section class="cc-pagehead" data-bg="coral">
        <div class="cc-wrap">
            <x-crumb>How it works</x-crumb>
            <h1 class="cc-title1">How it works</h1>
            <p class="cc-lead">One machine, two co-partners, one price: {{ $fmt(config('drinkwell.unit_price_cents')) }} a drink.</p>
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="cream">
        <div class="cc-wrap">
            <p class="cc-statement" data-reveal>Made for people who want a calm, steady way to earn, without running a business.</p>
            <p class="cc-lead" style="margin-inline:auto" data-reveal>If you can read an email and press a button, you can take part. We find you a co-partner, place the machine in a busy spot, and handle the restocking, counting and paying out.</p>
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="gray">
        <div class="cc-wrap">
            <h2 class="cc-huge" data-reveal><span>Start in</span><span>three steps</span></h2>
            <ol class="cc-cards3">
                <li class="cc-card cc-card-1" data-reveal><div class="cc-photo" aria-hidden="true"><img src="{{ asset('images/how/step-1.jpg') }}" alt="" loading="lazy" decoding="async"></div><div class="cc-card-body"><span class="cc-num">1</span><h3>Pairing</h3><p>Sign up, verify your email and give your date of birth. Co-partners are {{ config('drinkwell.min_age') }} or older. We then find you a co-partner and a machine, usually within 1 business day.</p></div></li>
                <li class="cc-card cc-card-2" data-reveal><div class="cc-photo" aria-hidden="true"><img src="{{ asset('images/how/step-2.jpg') }}" alt="" loading="lazy" decoding="async"></div><div class="cc-card-body"><span class="cc-num">2</span><h3>Deposit</h3><p>You are buying half of a machine your co-partner already owns, so you pay them directly. Upload your proof of payment and we confirm it.</p></div></li>
                <li class="cc-card cc-card-3" data-reveal><div class="cc-photo" aria-hidden="true"><img src="{{ asset('images/how/step-3.jpg') }}" alt="" loading="lazy" decoding="async"></div><div class="cc-card-body"><span class="cc-num">3</span><h3>Payouts</h3><p>From then on, every day's earnings are split 50/50 and added to your balance.</p></div></li>
            </ol>
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="mint">
        <div class="cc-wrap">
            <h2 class="cc-huge" data-reveal><span>Who does</span><span>what</span></h2>
            <p class="cc-lead" style="margin-inline:auto" data-reveal>You never restock or fix anything. Here is who does each job.</p>
            <div class="cc-who" data-reveal>
                <table>
                    <caption class="sr-only">Who does each job</caption>
                    <thead>
                        <tr><td></td><th scope="col"><span class="cc-who-head cc-who-1">You</span></th><th scope="col"><span class="cc-who-head cc-who-2"><span class="cc-who-long">Co-partner</span><span class="cc-who-short">Partner</span></span></th><th scope="col"><span class="cc-who-head cc-who-3"><span class="cc-who-long">{{ $brand->name }}</span><span class="cc-who-short">Us</span></span></th></tr>
                    </thead>
                    <tbody>
                        <tr><th scope="row">Pays half of the machine</th><td><span class="cc-tick" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span><span class="sr-only">Yes</span></td><td><span class="cc-tick" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span><span class="sr-only">Yes</span></td><td><span class="cc-tick-no" aria-hidden="true"></span><span class="sr-only">No</span></td></tr>
                        <tr><th scope="row">Receives the deposit and confirms it</th><td><span class="cc-tick-no" aria-hidden="true"></span><span class="sr-only">No</span></td><td><span class="cc-tick" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span><span class="sr-only">Yes</span></td><td><span class="cc-tick-no" aria-hidden="true"></span><span class="sr-only">No</span></td></tr>
                        <tr><th scope="row">Places and restocks the machine</th><td><span class="cc-tick-no" aria-hidden="true"></span><span class="sr-only">No</span></td><td><span class="cc-tick-no" aria-hidden="true"></span><span class="sr-only">No</span></td><td><span class="cc-tick" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span><span class="sr-only">Yes</span></td></tr>
                        <tr><th scope="row">Counts the sales and settles each day</th><td><span class="cc-tick-no" aria-hidden="true"></span><span class="sr-only">No</span></td><td><span class="cc-tick-no" aria-hidden="true"></span><span class="sr-only">No</span></td><td><span class="cc-tick" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span><span class="sr-only">Yes</span></td></tr>
                        <tr><th scope="row">Gets half of every daily payout</th><td><span class="cc-tick" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span><span class="sr-only">Yes</span></td><td><span class="cc-tick" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span><span class="sr-only">Yes</span></td><td><span class="cc-tick-no" aria-hidden="true"></span><span class="sr-only">No</span></td></tr>
                        <tr><th scope="row">Reviews each withdrawal</th><td><span class="cc-tick-no" aria-hidden="true"></span><span class="sr-only">No</span></td><td><span class="cc-tick-no" aria-hidden="true"></span><span class="sr-only">No</span></td><td><span class="cc-tick" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span><span class="sr-only">Yes</span></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section id="deposit" class="cc-section" data-bg="cream">
        <div class="cc-wrap">
            <h2 class="cc-huge" data-reveal><span>Paying your</span><span>co-partner</span></h2>
            <article class="cc-feature" data-reveal>
                <div class="cc-feature-photo" aria-hidden="true"><div class="cc-photo" aria-hidden="true"><img src="{{ asset('images/how/deposit.jpg') }}" alt="" loading="lazy" decoding="async"></div></div>
                <div>
                    <h3>Your deposit goes to your co-partner, never to us</h3>
                    <ul class="cc-points">
                        <li>There is no card form on this site, and nothing is paid to {{ $brand->name }}.</li>
                        <li>You are buying your half of a machine your co-partner already owns, so you pay them directly. Their name and payment details come by email and show on your dashboard.</li>
                        <li>Upload your proof of payment. Once it is confirmed, your share of the daily payouts starts.</li>
                    </ul>
                    <div class="cc-btn-row cc-btn-left"><a class="af-btn af-btn-black" href="{{ route('register') }}">Get started</a><a class="af-btn cc-btn-outline" href="{{ route('questions') }}">Read the questions</a></div>
                </div>
            </article>
        </div>
    </section>

    <section id="day" class="cc-section" data-bg="coral">
        <div class="cc-wrap">
            <h2 class="cc-huge" data-reveal><span>A day with</span><span>your machine</span></h2>
            <p class="cc-lead" data-reveal>Every drink is {{ $fmt(config('drinkwell.unit_price_cents')) }}, and the machine sells out every day.</p>
            <div class="cc-big-cards">
                <article class="cc-big-card" data-reveal><div class="cc-photo" aria-hidden="true"><img src="{{ asset('images/how/day-morning.jpg') }}" alt="" loading="lazy" decoding="async"></div><div class="cc-big-body"><h3>Restocked overnight, open at 06:00</h3><p>A driver refills it every night, so it is full again by morning.</p></div></article>
                <article class="cc-big-card" data-reveal><div class="cc-photo" aria-hidden="true"><img src="{{ asset('images/how/day-night.jpg') }}" alt="" loading="lazy" decoding="async"></div><div class="cc-big-body"><h3>Sold out by midnight, settled by morning</h3><p>Sales peak at lunch. At night the sales are counted, the fee comes off, and your share is added to your balance.</p></div></article>
            </div>
        </div>
    </section>

    <section id="split" class="cc-section" data-bg="amber">
        <div class="cc-wrap">
            @php $c = $tiers['commercial']; $e = $tiers['enterprise']; @endphp
            <h2 class="cc-huge" data-reveal><span>The daily</span><span>split</span></h2>
            <p class="cc-lead" data-reveal>One day with the {{ $c['label'] }}, worked out.</p>
            <ol class="cc-math" aria-label="One day of the {{ $c['label'] }}">
                <li data-reveal><b>{{ $fmt($c['gross']) }}</b><span>{{ $c['capacity'] }} drinks sold at {{ $fmt(config('drinkwell.unit_price_cents')) }}</span></li>
                <li data-reveal><b>- {{ $fmt($c['fee']) }}</b><span>Bottling and logistics fee</span></li>
                <li data-reveal><b>{{ $fmt($c['pool']) }}</b><span>Left to split</span></li>
                <li class="is-total" data-reveal><b>{{ $fmt($c['share']) }}</b><span>To you, and the same to your co-partner</span></li>
            </ol>
            <p class="cc-lead cc-after" data-reveal>The {{ $e['label'] }} works the same way: {{ $e['capacity'] }} drinks and {{ $fmt($e['share']) }} each. The fee moves a little from day to day, so your share stays between {{ $fmt($c['band_min']) }} and {{ $fmt($c['band_max']) }} on the {{ $c['label'] }}. See every figure on <a href="{{ route('earnings') }}">Your earnings</a>.</p>
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="gray">
        <div class="cc-wrap">
            <h2 class="cc-huge" data-reveal><span>Then your</span><span>money starts</span><span>to move</span></h2>
            <ol class="cc-cards3">
                <li class="cc-card cc-card-1" data-reveal><div class="cc-photo" aria-hidden="true"><img src="{{ asset('images/how/money-1.jpg') }}" alt="" loading="lazy" decoding="async"></div><div class="cc-card-body"><h3>Added every night</h3><p>When the day closes, your half of the pool is added to your balance.</p></div></li>
                <li class="cc-card cc-card-2" data-reveal><div class="cc-photo" aria-hidden="true"><img src="{{ asset('images/how/money-2.jpg') }}" alt="" loading="lazy" decoding="async"></div><div class="cc-card-body"><h3>Every day in your ledger</h3><p>Each day shows the sales, the fee and your share, to the cent, and you can download it.</p></div></li>
                <li class="cc-card cc-card-3" data-reveal><div class="cc-photo" aria-hidden="true"><img src="{{ asset('images/how/money-3.jpg') }}" alt="" loading="lazy" decoding="async"></div><div class="cc-card-body"><h3>Take it out when you like</h3><p>Ask from your dashboard for {{ $fmt(config('drinkwell.withdrawal_min_cents')) }} or more, confirm with a code we email you, and we review it within {{ config('drinkwell.withdrawal_review_days') }} business days.</p></div></li>
            </ol>
        </div>
    </section>
    <x-related :links="[['earnings', 'See what you could earn'], ['questions', 'Read the questions']]" />
</x-marketing-layout>
