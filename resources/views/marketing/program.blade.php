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

    <section class="cc-section" data-bg="gray">
        <div class="cc-wrap">
            <h2 class="cc-huge" data-reveal><span>From sign-up to</span><span>your first payout</span></h2>
            <ol class="cc-timeline">
                <li data-reveal><b>Day 1</b><h3>Create your account</h3><p>Give your name, email and date of birth, and set a password. We email you a six digit code to prove the address is yours.</p></li>
                <li data-reveal><b>Day 1</b><h3>Choose your machine</h3><p>Pick the Commercial Partner Unit or the Enterprise Hub Unit. You can see the deposit and the daily share for each before you choose.</p></li>
                <li data-reveal><b>Within 1 business day</b><h3>We find your co-partner</h3><p>Your dashboard shows a status while we look. When a partner is found you get an email and a card on your dashboard with their name, the machine and where it stands.</p></li>
                <li data-reveal><b>You pay</b><h3>Send your deposit to your co-partner</h3><p>Their payment details are on your dashboard and in your email. After you pay, upload a photo or PDF of your proof of payment so it can be checked.</p></li>
                <li data-reveal><b>We confirm</b><h3>Your deposit is confirmed</h3><p>Our team checks your proof and confirms the deposit. Your machine is then yours to share, and you get an email saying so.</p></li>
                <li data-reveal><b>Every night</b><h3>Your payouts begin</h3><p>The machine is settled when the day closes. Your half of the pool is added to your balance, and the day shows in your ledger.</p></li>
                <li data-reveal><b>Whenever you like</b><h3>Take your money out</h3><p>Ask for a withdrawal from your dashboard, confirm it with a code we email you, and our team reviews it within {{ config('drinkwell.withdrawal_review_days') }} business days.</p></li>
            </ol>
        </div>
    </section>

    <section class="cc-section" data-bg="coral">
        <div class="cc-wrap">
            <h2 class="cc-h2 cc-center" data-reveal>A day in the life of your machine</h2>
            <ol class="cc-day">
                <li data-reveal><b>Overnight</b><span>A driver restocks the machine so it opens full, every morning.</span></li>
                <li data-reveal><b>06:00</b><span>Sales open. Every drink is {{ $fmt(config('drinkwell.unit_price_cents')) }}.</span></li>
                <li data-reveal><b>Lunch</b><span>The busy hours, when most of the drinks go.</span></li>
                <li data-reveal><b>By midnight</b><span>The machine has sold out. The day closes.</span></li>
                <li data-reveal><b>Night</b><span>Sales are counted, the fee comes off, and the pool is split in half. Your share lands on your dashboard.</span></li>
            </ol>
        </div>
    </section>

    <section class="cc-section" data-bg="gray">
        <div class="cc-wrap">
            <h2 class="cc-h2 cc-center" data-reveal>Who does what</h2>
            <div class="cc-tiles">
                <article class="cc-tile is-coral" data-reveal><h3>You</h3><p>Pay your half of the deposit to your co-partner, keep your details up to date, and watch your machine from your dashboard.</p></article>
                <article class="cc-tile is-mint" data-reveal><h3>Your co-partner</h3><p>Owns the other half of the machine, receives your deposit, and shares every daily payout equally with you.</p></article>
                <article class="cc-tile is-amber" data-reveal><h3>{{ $brand->name }}</h3><p>Pairs you, stocks and settles the machine every day, keeps the books, and reviews each withdrawal.</p></article>
            </div>
        </div>
    </section>

    <section class="cc-section" data-bg="mint">
        <div class="cc-wrap">
            <h2 class="cc-h2 cc-center" data-reveal>What you can see on your dashboard</h2>
            <div class="cc-tiles cc-tiles-4">
                <article class="cc-tile is-cream" data-reveal><h3>Your balance</h3><p>What you have earned and what is ready to withdraw.</p></article>
                <article class="cc-tile is-cream" data-reveal><h3>The machine</h3><p>How many drinks are left, how many sold today, and its temperature.</p></article>
                <article class="cc-tile is-cream" data-reveal><h3>Your ledger</h3><p>One line for every day: drinks, sales, fee, pool and your payout. Download it any time.</p></article>
                <article class="cc-tile is-cream" data-reveal><h3>Your withdrawals</h3><p>Every request, whether it is waiting, approved or declined, and the reason if it was declined.</p></article>
            </div>
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
