@php
    $fmt = fn (int $c) => \App\Support\Money::format($c);
@endphp
<x-marketing-layout title="How it works">
    <section class="cc-pagehead" data-bg="coral">
        <div class="cc-wrap">
            <h1 class="cc-title1">How it works</h1>
            <p class="cc-lead">One machine, two co-partners, one price: {{ $fmt(config('drinkwell.unit_price_cents')) }} a drink.</p>
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="gray">
        <div class="cc-wrap">
            <h2 class="cc-huge" data-reveal><span>Start in</span><span>three steps</span></h2>
            <ol class="cc-cards3">
                <li class="cc-card cc-card-1" data-reveal><div class="cc-photo" aria-hidden="true"><span>Photo</span></div><div class="cc-card-body"><span class="cc-num">1</span><h3>Tell us about you</h3><p>Sign up, verify your email, and give your date of birth. Co-partners are {{ config('drinkwell.min_age') }} or older.</p></div></li>
                <li class="cc-card cc-card-2" data-reveal><div class="cc-photo" aria-hidden="true"><span>Photo</span></div><div class="cc-card-body"><span class="cc-num">2</span><h3>Get a partner</h3><p>We find you a co-partner and a machine, usually within 1 business day. Your email and your dashboard then show every detail.</p></div></li>
                <li class="cc-card cc-card-3" data-reveal><div class="cc-photo" aria-hidden="true"><span>Photo</span></div><div class="cc-card-body"><span class="cc-num">3</span><h3>Pay your co-partner</h3><p>You are buying half of a machine they already own, so you pay them directly. Once they confirm it, daily payouts begin.</p></div></li>
            </ol>
        </div>
    </section>

    <section id="day" class="cc-section cc-center" data-bg="mint">
        <div class="cc-wrap">
            <p class="cc-statement" data-reveal>Sales start at 06:00, peak at lunch, and every drink is sold by midnight.</p>
            <p class="cc-lead" style="margin-inline:auto" data-reveal>A driver restocks the machine overnight, so it is full again by morning.</p>
        </div>
    </section>

    <section id="split" class="cc-section" data-bg="amber">
        <div class="cc-wrap">
            <h2 class="cc-huge" data-reveal><span>The daily</span><span>split</span></h2>
            <div class="cc-big-cards">
                <article class="cc-big-card" data-reveal><div class="cc-photo" aria-hidden="true"><span>Photo</span></div><h3>Sales, minus the fee</h3></article>
                <article class="cc-big-card" data-reveal><div class="cc-photo" aria-hidden="true"><span>Photo</span></div><h3>Half to you, half to your co-partner</h3></article>
            </div>
            <p class="cc-lead cc-after" data-reveal>A bottling and logistics fee comes off first. The pool that is left is divided equally. See every figure on <a href="{{ route('earnings') }}">Your earnings</a>.</p>
        </div>
    </section>

    <section id="deposit" class="cc-section cc-center" data-bg="cream">
        <div class="cc-wrap">
            <h2 class="cc-h2" data-reveal>Paying your co-partner</h2>
            <p class="cc-lead" style="margin-inline:auto" data-reveal>There is no card form on this site. When you are paired, we email you your co-partner's name and payment details, and the same details show on your dashboard. You are buying your half of a machine they already own, so the deposit goes straight to them and never to us. Your share starts the day they confirm it.</p>
            <div class="cc-btn-row" data-reveal><a class="af-btn af-btn-black" href="{{ route('register') }}">Get started</a><a class="af-btn cc-btn-outline" href="{{ route('questions') }}">Read the questions</a></div>
        </div>
    </section>
</x-marketing-layout>
