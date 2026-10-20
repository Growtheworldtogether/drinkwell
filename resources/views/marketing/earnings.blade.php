@php
    $fmt = fn (int $c) => \App\Support\Money::format($c);
@endphp
<x-marketing-layout title="Your earnings">
    <section class="cc-pagehead" data-bg="coral">
        <div class="cc-wrap">
            <div><h1 class="cc-title1">Your earnings</h1>
            <p class="cc-lead">Every drink sells at {{ $fmt(config('drinkwell.unit_price_cents')) }}. Here is exactly how one day adds up, and how fast your deposit comes back.</p></div>
            <img class="cc-art cc-pagehead-art" src="{{ asset('images/earnings.svg') }}" alt="" loading="lazy">
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="gray">
        <div class="cc-wrap">
            <h2 class="cc-h2" data-reveal>The daily split</h2>
            <p class="cc-lead" style="margin-inline:auto" data-reveal>Sales come in, the bottling and logistics fee comes off, and the rest is split equally between you and your co-partner.</p>
            <div class="cc-scroll" data-reveal>
                <table class="cc-table">
                    <caption class="sr-only">Daily figures by machine</caption>
                    <thead><tr><th>Machine</th><th>Drinks</th><th>Sales</th><th>Fee</th><th>Pool</th><th>Your share</th><th>Usual range</th></tr></thead>
                    <tbody>
                        @foreach ($tiers as $t)
                            <tr>
                                <td>{{ $t['label'] }}</td><td>{{ $t['capacity'] }}</td><td>{{ $fmt($t['gross']) }}</td><td>{{ $fmt($t['fee']) }}</td><td>{{ $fmt($t['pool']) }}</td><td>{{ $fmt($t['share']) }}</td><td>{{ $fmt($t['band_min']) }} to {{ $fmt($t['band_max']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
