@php
    $fmt = fn (int $c) => \App\Support\Money::format($c);
    $calc = collect($tiers)->map(fn ($t) => ['label' => $t['label'], 'stake' => $t['stake'], 'share' => $t['share'], 'capacity' => $t['capacity']])->all();
@endphp
<x-marketing-layout title="Co-own a vending machine">
    <section class="cc-hero cc-tint-butter" data-tint-cycle>
        <span class="cc-orb cc-orb-a" aria-hidden="true"></span><span class="cc-orb cc-orb-b" aria-hidden="true"></span><span class="cc-orb cc-orb-c" aria-hidden="true"></span>
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
                    <span class="cc-chip">Co-partners</span>
                    <div class="cc-slot" style="height:100%;border-radius:24px" role="img" aria-label="Photo slot: two seniors at a machine">Photo slot 16:9 / two co-partners at their machine</div>
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

    <div class="cc-ribbon" aria-label="Facts about the program">
        <div class="cc-ribbon-row">
            @for ($n = 0; $n < 2; $n++)
                <span>50/50 co-partners</span><span>{{ $fmt(config('drinkwell.unit_price_cents')) }} a drink</span><span>Sells out daily</span><span>Paired at once</span><span>Age {{ config('drinkwell.min_age') }} and over</span><span>Classroom simulation</span>
            @endfor
        </div>
    </div>

    <section class="cc-section">
        <div class="af-wrap">
            <p class="cc-statement" data-reveal>We are refreshing what it means to co-own: one drink, one payout at a time.</p>
            <div class="af-ctas" style="margin-top:32px" data-reveal>
                <a class="af-btn af-btn-black" href="{{ route('program') }}">Discover how it works</a>
            </div>
            <div class="cc-stats">
                <div class="cc-stat" data-reveal><b data-count="{{ $machines }}">{{ $machines }}</b>machines in the network</div>
                @foreach ($tiers as $t)
                    <div class="cc-stat" data-reveal><b data-count="{{ $t['capacity'] }}">{{ $t['capacity'] }}</b>drinks a day, {{ strtolower($t['label']) }}</div>
                @endforeach
                <div class="cc-stat" data-reveal><b data-count="{{ config('drinkwell.min_age') }}">{{ config('drinkwell.min_age') }}</b>minimum age to join</div>
            </div>
        </div>
    </section>

    <section class="cc-section">
        <div class="af-wrap">
            <h2 class="cc-title" data-reveal>Two machines, one 50/50 split.</h2>
            <p class="cc-lead">Every machine sells out every day. The platform fee is taken first, the rest is split in half.</p>
            <div class="cc-options">
                @foreach ($tiers as $key => $t)
                    <article data-reveal class="cc-option" aria-labelledby="tier-{{ $key }}">
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
            <h2 class="cc-title" data-reveal>Yield calculator.</h2>
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

    <section class="cc-section cc-on-lilac">
        <div class="af-wrap">
            <h2 class="cc-title" data-reveal>Latest from the machines.</h2>
            <div class="cc-carousel">
                <div class="cc-track" data-drag>
                    @foreach ([
                        ['Sales', 'Commercial machine sold out at 21:40 yesterday'],
                        ['Restock', 'Overnight drivers refilled every machine by 05:30'],
                        ['Payouts', 'Daily payouts landed inside the band for every partner'],
                        ['Pairing', 'New co-partners were paired the same minute they finished signing up'],
                        ['Stock', 'Hospital machines peak at the lunch hour'],
                    ] as [$tag, $headline])
                        <article class="cc-card">
                            <div class="cc-slot">Photo slot 4:3 / {{ strtolower($tag) }}</div>
                            <span class="cc-chip">{{ $tag }}</span>
                            <h3>{{ $headline }}</h3>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="cc-section">
        <div class="af-wrap">
            <h2 class="cc-title" data-reveal>Where machines stand.</h2>
            <div class="cc-show">
                <a href="{{ route('program') }}"><span class="cc-dot"></span>Hospitals</a>
                <a href="{{ route('program') }}"><span class="cc-dot"></span>Stations</a>
                <a href="{{ route('program') }}"><span class="cc-dot"></span>Campuses</a>
            </div>
        </div>
    </section>

    <section class="cc-section cc-on-mint">
        <div class="af-wrap">
            <h2 class="cc-title" data-reveal>How pairing works.</h2>
            <ol class="cc-steps">
                <li><h3>1. Tell us about you</h3><p>Sign up, verify your email, and give your date of birth. Co-partners are {{ config('drinkwell.min_age') }} or older.</p></li>
                <li><h3>2. Get paired at once</h3><p>You get a machine and a co-partner right away. Your co-partner is simulated, so nobody has to wait for anyone.</p></li>
                <li><h3>3. Settle off the platform</h3><p>The deposit is sent by wire, not by card on this site. Once it is confirmed, daily payouts begin.</p></li>
            </ol>
        </div>
    </section>
    <section class="cc-section">
        <div class="af-wrap">
            <div class="cc-help" data-reveal>
                <h2 class="cc-title" data-reveal>We are here to help.</h2>
                <form action="{{ route('program') }}" method="get" role="search">
                    <label class="sr-only" for="help-q">Search the program</label>
                    <input id="help-q" name="q" type="search" placeholder="Ask about deposits, payouts or pairing">
                    <button class="af-btn af-btn-black" type="submit">Search</button>
                </form>
                <div class="cc-chips">
                    <a href="{{ route('program') }}">How payouts work</a>
                    <a href="{{ route('program') }}">Paying the deposit</a>
                    <a href="{{ route('terms') }}">Terms</a>
                    <a href="{{ route('privacy') }}">Privacy</a>
                </div>
            </div>
        </div>
    </section>

    <section class="cc-cta">
        <div class="af-wrap">
            <h2 class="cc-title" data-reveal>Ready to meet your co-partner?</h2>
            <div class="af-ctas" style="margin-top:32px" data-reveal>
                @auth
                    <a class="af-btn" href="{{ route('dashboard') }}">Open dashboard</a>
                @else
                    <a class="af-btn" href="{{ route('register') }}">Get started</a>
                @endauth
            </div>
        </div>
    </section>
</x-marketing-layout>
