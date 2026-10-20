@php
    $fmt = fn (int $c) => \App\Support\Money::format($c);
    $calc = collect($tiers)->map(fn ($t) => ['label' => $t['label'], 'stake' => $t['stake'], 'share' => $t['share'], 'capacity' => $t['capacity']])->all();
@endphp
<x-marketing-layout title="Co-own a vending machine">
    <section class="cc-hero">
        <div class="af-wrap">
            <h1 class="cc-display"><span>Own half</span><span>of a smart</span><span>vending machine.</span></h1>
            <p class="cc-lead">{{ $brand->name }} pairs seniors as 50/50 co-partners on a machine that sells every drink at {{ $fmt(config('drinkwell.unit_price_cents')) }}. Watch stock, sales and payouts in real time. This is a classroom simulation.</p>
            <div class="af-ctas">
                @auth
                    <a class="af-btn af-btn-black" href="{{ route('dashboard') }}">Open dashboard</a>
                @else
                    <a class="af-btn af-btn-black" href="{{ route('register') }}">Get started</a>
                @endauth
                <a class="af-btn cc-btn-outline" href="{{ route('program') }}">How it works</a>
            </div>
            <div class="cc-photos">
                <div class="cc-photo">
                    <span class="cc-chip">Live map</span>
                    <div data-map data-public-map data-src="{{ route('public.machines') }}" role="img" aria-label="Map of facilities that host a machine"></div>
                </div>
                <div class="cc-photo-stack">
                    @foreach ($tiers as $key => $t)
                        <div class="cc-photo-card">
                            <span class="cc-chip cc-chip-dark">{{ $t['capacity'] }} drinks a day</span>
                            <h3>{{ $t['label'] }}</h3>
                            <p>Your share about {{ $fmt($t['share']) }} a day</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="cc-section">
        <div class="af-wrap">
            <h2 class="cc-title">Two machines, one 50/50 split.</h2>
            <p class="cc-lead">Every machine sells out every day. The platform fee is taken first, the rest is split in half.</p>
            <div class="cc-options">
                @foreach ($tiers as $key => $t)
                    <article class="cc-option" aria-labelledby="tier-{{ $key }}">
                        <h3 id="tier-{{ $key }}">{{ $t['label'] }}</h3>
                        <p class="cc-big">{{ $fmt($t['share']) }}</p>
                        <p class="text-af-700">your share a day, deposit back in about {{ $t['payback'] }} days</p>
                        <dl class="cc-facts">
                            <div><dt>Machine cost</dt><dd>{{ $fmt($t['machine_cost']) }}</dd></div>
                            <div><dt>Your 50% deposit</dt><dd>{{ $fmt($t['stake']) }}</dd></div>
                            <div><dt>Capacity (sells out daily)</dt><dd>{{ $t['capacity'] }} drinks</dd></div>
                            <div><dt>Gross a day</dt><dd>{{ $fmt($t['gross']) }}</dd></div>
                            <div><dt>Bottling and logistics fee</dt><dd>{{ $fmt($t['fee']) }}</dd></div>
                            <div><dt>Pool to split</dt><dd>{{ $fmt($t['pool']) }}</dd></div>
                            <div><dt>Daily band</dt><dd>{{ $fmt($t['band_min']) }} to {{ $fmt($t['band_max']) }}</dd></div>
                        </dl>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="cc-section cc-on-peach">
        <div class="af-wrap" x-data="yieldCalculator(@js($calc))">
            <h2 class="cc-title">Yield calculator.</h2>
            <p class="cc-lead">Pick a machine and see how fast the deposit comes back at the usual daily share.</p>
            <div class="cc-options">
                <div class="cc-option">
                    <fieldset class="min-w-0">
                        <legend class="mb-3 text-label uppercase tracking-label text-af-500">Option</legend>
                        <div class="flex flex-col gap-3">
                            @foreach ($tiers as $key => $t)
                                <label class="cc-pick">
                                    <input type="radio" name="calc-tier" value="{{ $key }}" x-model="tier">
                                    <span class="block font-medium">{{ $t['label'] }} <span class="font-mono tabular-nums">({{ $fmt($t['stake']) }} deposit)</span></span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <p class="mt-6 text-label uppercase tracking-label text-af-500" id="calc-days-label">Days</p>
                    <div class="cc-stepper">
                        <button type="button" aria-label="One day less" @click="days = Math.max(1, days - 1)">-</button>
                        <output aria-labelledby="calc-days-label" x-text="days"></output>
                        <button type="button" aria-label="One day more" @click="days = Math.min(60, days + 1)">+</button>
                    </div>
                    <input id="calc-days" class="dw-range" type="range" min="1" max="60" step="1" x-model.number="days" aria-labelledby="calc-days-label">
                    <dl class="cc-facts">
                        <div><dt>Share a day</dt><dd x-text="money(t.share)"></dd></div>
                        <div><dt>Earned in <span x-text="days"></span> days</dt><dd x-text="money(total)"></dd></div>
                        <div><dt>Deposit</dt><dd x-text="money(t.stake)"></dd></div>
                        <div><dt>Left after deposit</dt><dd x-text="money(net)"></dd></div>
                        <div><dt>Deposit back in</dt><dd><span x-text="paybackExact"></span> days (day <span x-text="payback"></span>)</dd></div>
                    </dl>
                </div>
                <div class="cc-option">
                    <p class="text-label uppercase tracking-label text-af-500">Amortization (cumulative payout against deposit)</p>
                    <div class="dw-curve mt-3"><canvas x-ref="curve" role="img" aria-label="Cumulative payout against the deposit"></canvas></div>
                </div>
            </div>
            <p class="mt-6 text-af-700">Figures use the middle of the daily band. Real days vary by a small amount and are shown on your dashboard. Simulation only.</p>
        </div>
    </section>

    <section class="cc-section">
        <div class="af-wrap">
            <h2 class="cc-title">Where machines stand.</h2>
            <div class="cc-show">
                <a href="{{ route('program') }}"><span class="cc-dot"></span>Hospitals</a>
                <a href="{{ route('program') }}"><span class="cc-dot"></span>Stations</a>
                <a href="{{ route('program') }}"><span class="cc-dot"></span>Campuses</a>
            </div>
        </div>
    </section>

    <section class="cc-section cc-on-mint">
        <div class="af-wrap">
            <h2 class="cc-title">How pairing works.</h2>
            <ol class="cc-steps">
                <li><h3>1. Tell us about you</h3><p>Sign up, verify your email, and give your date of birth. Co-partners are {{ config('drinkwell.min_age') }} or older.</p></li>
                <li><h3>2. Get paired at once</h3><p>You get a machine and a co-partner right away. Your co-partner is simulated, so nobody has to wait for anyone.</p></li>
                <li><h3>3. Settle off the platform</h3><p>The deposit is sent by wire, not by card on this site. Once it is confirmed, daily payouts begin.</p></li>
            </ol>
        </div>
    </section>
</x-marketing-layout>
