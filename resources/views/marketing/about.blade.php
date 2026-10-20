@php
    $fmt = fn (int $c) => \App\Support\Money::format($c);
@endphp
<x-marketing-layout title="About">
    <section class="cc-pagehead" data-bg="cream">
        <div class="cc-wrap">
            <div><h1 class="cc-title1">About {{ $brand->name }}</h1>
            <p class="cc-lead">We pair people aged {{ config('drinkwell.min_age') }} and over as equal co-owners of a vending machine, and show every sale and payout as it happens.</p></div>
            <img class="cc-art cc-pagehead-art" src="{{ asset('images/partners.svg') }}" alt="" loading="lazy">
        </div>
    </section>

    <section class="cc-section" data-bg="gray">
        <div class="cc-wrap cc-prosewrap">
            <div class="cc-prose" data-reveal>
                <h2>What we do</h2>
                <p>Two people each put in half the cost of a drink machine and share what it earns. Machines stand in busy places such as hospitals, train stations and airports, and every drink sells at {{ $fmt(config('drinkwell.unit_price_cents')) }}.</p>
                <h2>Who it is for</h2>
                <p>People aged {{ config('drinkwell.min_age') }} and over who want a clear, steady, easy-to-follow way to earn. Everything is written in plain words and the text on every page can be made bigger.</p>
                <h2>How we count money</h2>
                <p>Every amount is counted in whole cents, and the pool is always an even number, so nobody is ever short a cent. The fee comes off first, then the pool is split equally.</p>
                <h2>What we do not do</h2>
                <p>We never take card payments on this site. Deposits are sent by bank wire, and the details are in your pairing email. We keep your bank details encrypted and show only the last four digits.</p>
                <h2>Get in touch</h2>
                <p>Write to <a href="mailto:{{ $brand->supportEmail }}" class="break-anywhere">{{ $brand->supportEmail }}</a>. Read the <a href="{{ route('questions') }}">questions</a> first, they may already have your answer.</p>
            </div>
        </div>
    </section>
</x-marketing-layout>
