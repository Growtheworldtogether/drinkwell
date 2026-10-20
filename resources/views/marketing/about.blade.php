@php
    $fmt = fn (int $c) => \App\Support\Money::format($c);
@endphp
<x-marketing-layout title="About">
    <section class="cc-pagehead" data-bg="cream">
        <div class="cc-wrap">
            <h1 class="cc-title1">About {{ $brand->name }}</h1>
            <p class="cc-lead">Equal co-owners, plain words, and every sale and payout in view.</p>
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="mint">
        <div class="cc-wrap">
            <p class="cc-statement" data-reveal>We pair people aged {{ config('drinkwell.min_age') }} and over as equal co-owners of a vending machine.</p>
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="gray">
        <div class="cc-wrap">
            <h2 class="cc-huge" data-reveal><span>What we</span><span>stand for</span></h2>
            <ol class="cc-cards3">
                <li class="cc-card cc-card-1" data-reveal><div class="cc-photo" aria-hidden="true"><span>Photo</span></div><div class="cc-card-body"><h3>Plain words</h3><p>Everything is written clearly, and the text on every page can be made bigger.</p></div></li>
                <li class="cc-card cc-card-2" data-reveal><div class="cc-photo" aria-hidden="true"><span>Photo</span></div><div class="cc-card-body"><h3>Honest money</h3><p>Every amount is counted in whole cents and the pool is always even, so nobody is ever short a cent. Sales sell at {{ $fmt(config('drinkwell.unit_price_cents')) }} a drink.</p></div></li>
                <li class="cc-card cc-card-3" data-reveal><div class="cc-photo" aria-hidden="true"><span>Photo</span></div><div class="cc-card-body"><h3>Your details stay safe</h3><p>Your deposit goes to your co-partner, never to us, and there are no card payments on this site. Bank details are stored encrypted and only the last four digits are shown.</p></div></li>
            </ol>
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="amber">
        <div class="cc-wrap">
            <h2 class="cc-h2" data-reveal>Get in touch</h2>
            <p class="cc-lead" style="margin-inline:auto" data-reveal>Write to <a href="mailto:{{ $brand->supportEmail }}" class="break-anywhere"><strong>{{ $brand->supportEmail }}</strong></a>. Read the <a href="{{ route('questions') }}"><strong>questions</strong></a> first, they may already have your answer.</p>
        </div>
    </section>
</x-marketing-layout>
