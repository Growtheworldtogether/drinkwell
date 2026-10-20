@php
    $fmt = fn (int $c) => \App\Support\Money::format($c);
@endphp
<x-marketing-layout title="How it works">
    <section class="cc-pagehead" data-bg="coral">
        <div class="cc-wrap">
            <div><h1 class="cc-title1">How it works</h1>
            <p class="cc-lead">One machine, two co-partners, one price: {{ $fmt(config('drinkwell.unit_price_cents')) }} a drink.</p></div>
            <img class="cc-art cc-pagehead-art" src="{{ asset('images/machine.svg') }}" alt="" loading="lazy">
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="gray">
        <div class="cc-wrap">
            <h2 class="cc-h2" data-reveal>Start in three steps</h2>
            <ol class="cc-steps">
                <li class="cc-step" data-reveal><h3>1. Tell us about you</h3><p>Sign up, verify your email, and give your date of birth. Co-partners are {{ config('drinkwell.min_age') }} or older.</p></li>
                <li class="cc-step" data-reveal><h3>2. Get paired at once</h3><p>You get a machine and a co-partner right away. Your pairing email has every detail.</p></li>
                <li class="cc-step" data-reveal><h3>3. Pay the deposit</h3><p>The deposit is sent by bank wire, not by card on this site. Once it is confirmed, daily payouts begin.</p></li>
            </ol>
        </div>
    </section>

    <section class="cc-section" data-bg="gray">
        <div class="cc-wrap cc-prosewrap">
            <div class="cc-prose" data-reveal>
                <h2 id="split">The daily split</h2>
                <p>Gross sales are the number of drinks times the price. A bottling and logistics fee comes off, and the pool is split equally between the two co-partners. The fee changes a little from day to day, so your payout stays inside a range. See the full figures on <a href="{{ route('earnings') }}">Your earnings</a>.</p>
                <h2 id="day">A day in the life of a machine</h2>
                <p>Sales start at 06:00 and follow a daily curve, with a morning rush and a lunch peak. Every drink is sold by midnight, and a driver restocks the machine overnight.</p>
                <h2 id="deposit">Paying the deposit</h2>
                <p>There is no card form on this site. After pairing, you get an email with your machine, your co-partner's contact details, a reference and a ready-made message to send. Your share stays "pending settlement" until the deposit is confirmed.</p>
            </div>
            <div class="cc-btn-row"><a class="af-btn af-btn-black" href="{{ route('register') }}">Get started</a><a class="af-btn cc-btn-outline" href="{{ route('questions') }}">Read the questions</a></div>
        </div>
    </section>
</x-marketing-layout>
