@php
    $facilities = collect($facilities);
    $fmt = fn (int $c) => \App\Support\Money::format($c);
    $calc = collect($tiers)->map(fn ($t) => ['label' => $t['label'], 'stake' => $t['stake'], 'share' => $t['share'], 'capacity' => $t['capacity']])->all();
@endphp
<x-marketing-layout title="Co-own a vending machine">
    <section class="cc-hero" data-bg="coral">
        <div class="cc-wrap">
            <h1 class="cc-display"><span>We Co-own</span><span>the Machine</span><span>and Share the</span><span class="cc-word" data-words="Daily Payout|Morning Rush|Lunch Peak">Daily Payout</span></h1>
            <p class="cc-lead">{{ $brand->name }} pairs seniors as 50/50 co-partners on a machine that sells every drink at {{ $fmt(config('drinkwell.unit_price_cents')) }}. This is a classroom simulation.</p>
            <div class="af-ctas">
                @auth
                    <a class="af-btn af-btn-black" href="{{ route('dashboard') }}">Open dashboard</a>
                @else
                    <a class="af-btn af-btn-black" href="{{ route('register') }}">Get started</a>
                @endauth
                <a class="af-btn cc-btn-outline" href="{{ route('program') }}">How it works</a>
            </div>
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="mint">
        <div class="cc-wrap">
            <p class="cc-statement" data-reveal>At {{ $brand->name }}, we are refreshing what it means to co&#8209;own: one drink, one payout at a time.</p>
            <div class="cc-btn-row" data-reveal><a class="af-btn af-btn-black" href="{{ route('program') }}">Discover how it works</a></div>
        </div>
    </section>

    <section id="machines" class="cc-section cc-center" data-bg="gray">
        <div class="cc-wrap">
            <h2 class="cc-h2" data-reveal>Two machines, one 50/50 split</h2>
            <p class="cc-lead" style="margin-inline:auto" data-reveal>Every machine sells out every day. The platform fee is taken first, the rest is split in half.</p>
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

    <section id="calculator" class="cc-section cc-center" data-bg="gray">
        <div class="cc-wrap" x-data="yieldCalculator(@js($calc))">
            <h2 class="cc-h2" data-reveal>Yield calculator</h2>
            <p class="cc-lead" style="margin-inline:auto" data-reveal>Pick a machine and see how fast the deposit comes back at the usual daily share.</p>
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

    <section class="cc-section" data-bg="amber">
        <div class="cc-wrap">
            <h2 class="cc-huge" data-reveal><span>Our Promise</span><span>& Daily</span><span>Payout</span></h2>
            <div class="cc-big-cards">
                <article class="cc-big-card" data-reveal><h3>What makes a co-partnership work</h3><small>Photo slot 4:5</small></article>
                <article class="cc-big-card" data-reveal><h3>Learn more about the program</h3><small>Photo slot 4:5</small></article>
            </div>
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="cream">
        <div class="cc-wrap"><h2 class="cc-h2" data-reveal>Say "Hello" to the machines</h2></div>
        <div class="cc-rows" aria-label="Facilities that host a machine">
            @foreach ([$facilities->take(ceil($facilities->count() / 2)), $facilities->slice(ceil($facilities->count() / 2))] as $r => $row)
                <div class="cc-row {{ $r ? 'cc-row-right' : '' }}">
                    <div class="cc-row-track">
                        @for ($n = 0; $n < 2; $n++)
                            @foreach ($row as $k => $name)
                                <a href="{{ route('program') }}" @if ($n) aria-hidden="true" tabindex="-1" @endif><span class="cc-dot cc-dot-{{ ($k + $r) % 4 }}">{{ mb_substr($name, 0, 1) }}</span>{{ $name }}<span class="cc-explore">Explore</span></a>
                            @endforeach
                        @endfor
                    </div>
                </div>
            @endforeach
        </div>
        <div class="cc-btn-row"><a class="af-btn af-btn-black" href="{{ route('program') }}">Meet the rest</a></div>
    </section>

    <section class="cc-section" data-bg="gray">
        <div class="cc-wrap">
            <h2 class="cc-huge" data-reveal><span>Unleash</span><span>Your Earnings</span></h2>
        </div>
        <div class="cc-tabs-row">
            <a class="cc-tabcard" href="{{ route('register') }}"><span>Pairing</span></a>
            <a class="cc-tabcard" href="{{ route('program') }}#deposit"><span>Deposit</span></a>
            <a class="cc-tabcard" href="{{ route('program') }}#split"><span>Payouts</span></a>
            <a class="cc-tabcard" href="{{ route('login') }}"><span>Dashboard</span></a>
        </div>
        <div class="cc-wrap cc-center">
            <p class="cc-lead" style="margin-inline:auto" data-reveal>We are a team, helping seniors earn with a share in a machine and build a steady daily income together.</p>
            <div class="cc-btn-row" data-reveal><a class="af-btn af-btn-black" href="{{ route('register') }}">Find your machine here</a></div>
        </div>
    </section>

    <section class="cc-section" data-bg="gray">
        <div class="cc-wrap">
            <div class="cc-help" data-reveal>
                <h2 class="cc-h2">We're here to help</h2>
                <form action="{{ route('program') }}" method="get" role="search">
                    <label class="sr-only" for="help-q">Search the program</label>
                    <input id="help-q" name="q" type="search" placeholder="Start typing here...">
                    <button class="af-btn af-btn-black" type="submit" aria-label="Search">Search</button>
                </form>
                <div class="cc-chips">
                    <a href="{{ route('program') }}">How payouts work</a>
                    <a href="{{ route('program') }}#deposit">Paying the deposit</a>
                    <a href="{{ route('terms') }}">Terms</a>
                    <a href="{{ route('privacy') }}">Privacy</a>
                </div>
            </div>
        </div>
    </section>
</x-marketing-layout>
