@php
    $fmt = fn (int $c) => \App\Support\Money::format($c);
    $calc = collect($tiers)->map(fn ($t) => ['label' => $t['label'], 'stake' => $t['stake'], 'share' => $t['share'], 'capacity' => $t['capacity']])->all();
@endphp
<x-marketing-layout title="Co-own a vending machine">
    <section class="af-hero">
        <div class="af-hero-copy">
            <h1 class="af-h1">Own half of a smart vending machine.</h1>
            <p class="af-lead">{{ $brand->name }} pairs seniors as 50/50 co-partners on a machine that sells every drink at {{ $fmt(config('drinkwell.unit_price_cents')) }}. Watch stock, sales and payouts in real time. This is a classroom simulation.</p>
            <div class="af-ctas">
                @auth
                    <a class="af-btn af-btn-black" href="{{ route('dashboard') }}">Open dashboard</a>
                @else
                    <a class="af-btn af-btn-black" href="{{ route('register') }}">Get started</a>
                @endauth
                <a class="af-btn af-btn-soft" href="{{ route('program') }}">How it works</a>
            </div>
        </div>
        <div class="af-hero-map">
            <div data-map data-public-map data-src="{{ route('public.machines') }}" role="img" aria-label="Map of facilities that host a machine"></div>
        </div>
    </section>

    <section class="af-band">
        <div class="af-wrap">
            <h2 class="af-h1">Two machines, one 50/50 split</h2>
            <p class="af-lead">Every machine sells out every day. The platform fee is taken first, the rest is split in half.</p>
            <div class="dw-grid2 mt-6">
                @foreach ($tiers as $key => $t)
                    <article class="dw-card" aria-labelledby="tier-{{ $key }}">
                        <h3 id="tier-{{ $key }}">{{ $t['label'] }}</h3>
                        <dl class="dw-rows">
                            <div><dt>Machine cost</dt><dd>{{ $fmt($t['machine_cost']) }}</dd></div>
                            <div><dt>Your 50% deposit</dt><dd>{{ $fmt($t['stake']) }}</dd></div>
                            <div><dt>Capacity (sells out daily)</dt><dd>{{ $t['capacity'] }} drinks</dd></div>
                            <div><dt>Gross a day</dt><dd>{{ $fmt($t['gross']) }}</dd></div>
                            <div><dt>Bottling and logistics fee</dt><dd>{{ $fmt($t['fee']) }}</dd></div>
                            <div><dt>Pool to split</dt><dd>{{ $fmt($t['pool']) }}</dd></div>
                            <div><dt>Your share a day</dt><dd>{{ $fmt($t['share']) }}</dd></div>
                            <div><dt>Daily band</dt><dd>{{ $fmt($t['band_min']) }} to {{ $fmt($t['band_max']) }}</dd></div>
                            <div><dt>Deposit back in</dt><dd>about {{ $t['payback'] }} days</dd></div>
                        </dl>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="af-band af-band-soft">
        <div class="af-wrap" x-data="yieldCalculator(@js($calc))">
            <h2 class="af-h1">Yield calculator</h2>
            <p class="af-lead">Pick a machine and see how fast the deposit comes back at the usual daily share.</p>
            <div class="dw-grid2 mt-6">
                <div class="dw-card">
                    <fieldset class="min-w-0">
                        <legend class="mb-3 text-label uppercase tracking-label text-af-500">Option</legend>
                        <div class="flex flex-col gap-3">
                            @foreach ($tiers as $key => $t)
                                <label class="af-choice">
                                    <input type="radio" name="calc-tier" value="{{ $key }}" x-model="tier">
                                    <span class="block font-medium">{{ $t['label'] }} <span class="font-mono tabular-nums">({{ $fmt($t['stake']) }} deposit)</span></span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <label class="mt-6 block text-label uppercase tracking-label text-af-500" for="calc-days">Days: <span class="font-mono tabular-nums" x-text="days"></span></label>
                    <input id="calc-days" class="dw-range" type="range" min="1" max="60" step="1" x-model.number="days">
                    <dl class="dw-rows">
                        <div><dt>Share a day</dt><dd x-text="money(t.share)"></dd></div>
                        <div><dt>Earned in <span x-text="days"></span> days</dt><dd x-text="money(total)"></dd></div>
                        <div><dt>Deposit</dt><dd x-text="money(t.stake)"></dd></div>
                        <div><dt>Left after deposit</dt><dd x-text="money(net)"></dd></div>
                        <div><dt>Deposit back in</dt><dd><span x-text="paybackExact"></span> days (day <span x-text="payback"></span>)</dd></div>
                    </dl>
                </div>
                <div class="dw-card">
                    <p class="text-label uppercase tracking-label text-af-500">Amortization (cumulative payout against deposit)</p>
                    <div class="dw-curve mt-3"><canvas x-ref="curve" role="img" aria-label="Cumulative payout against the deposit"></canvas></div>
                </div>
            </div>
            <p class="mt-6 text-af-700">Figures use the middle of the daily band. Real days vary by a small amount and are shown on your dashboard. Simulation only.</p>
        </div>
    </section>

    <section class="af-band">
        <div class="af-wrap">
            <h2 class="af-h1">How pairing works</h2>
            <ol class="dw-grid3 mt-6">
                <li class="dw-card"><h3>1. Tell us about you</h3><p class="mt-2 text-af-700">Sign up, verify your email, and give your date of birth. Co-partners are {{ config('drinkwell.min_age') }} or older.</p></li>
                <li class="dw-card"><h3>2. Get paired at once</h3><p class="mt-2 text-af-700">You get a machine and a co-partner right away. Your co-partner is simulated, so nobody has to wait for anyone.</p></li>
                <li class="dw-card"><h3>3. Settle off the platform</h3><p class="mt-2 text-af-700">The deposit is sent by wire, not by card on this site. Once it is confirmed, daily payouts begin.</p></li>
            </ol>
        </div>
    </section>
</x-marketing-layout>
