@php
    $facilities = collect($facilities);
    $fmt = fn (int $c) => \App\Support\Money::format($c);
    $calc = collect($tiers)->map(fn ($t) => ['label' => $t['label'], 'stake' => $t['stake'], 'share' => $t['share'], 'capacity' => $t['capacity']])->all();
@endphp
<x-marketing-layout title="Co-own a vending machine">
    <section class="cc-story" data-bg="coral" aria-label="{{ $brand->name }}"
        x-data="{ i: 0, stop: false, slides: [
            { tag: 'Ages {{ config('drinkwell.min_age') }} and over', title: 'Co-own a drink vending machine with a partner we find for you', link: '{{ route('program') }}', label: 'How it works' },
            { tag: 'Deposit', title: 'Buy half of the machine from your co-partner', link: '{{ route('program') }}#deposit', label: 'How the deposit works' },
            { tag: 'Payouts', title: 'Share the daily payout, with every cent shown', link: '{{ route('earnings') }}', label: 'See your earnings' }
        ] }"
        x-init="setInterval(() => { if (! stop && ! document.hidden) i = (i + 1) % 3 }, 6000)">
        <h1 class="sr-only">We Co-own the Machine and Share the Daily Payout</h1>
        <div class="cc-story-card">
            <div class="cc-photo cc-story-photos" aria-hidden="true"><img src="{{ asset('images/home/story-1.jpg') }}" alt="" loading="lazy" decoding="async" x-bind:class="{ 'is-on': i === 0 }" class="is-on"><img src="{{ asset('images/home/story-2.jpg') }}" alt="" loading="lazy" decoding="async" x-bind:class="{ 'is-on': i === 1 }"><img src="{{ asset('images/home/story-3.jpg') }}" alt="" loading="lazy" decoding="async" x-bind:class="{ 'is-on': i === 2 }"></div>
            <div class="cc-story-dots" x-bind:class="{ 'is-held': stop }" role="group" aria-label="Choose a story">
                <button type="button" class="cc-story-dot" x-bind:class="{ 'is-on': i === 0 }" x-bind:aria-pressed="(i === 0).toString()" aria-label="Pairing" x-on:click="i = 0; stop = true">1</button>
                <button type="button" class="cc-story-dot" x-bind:class="{ 'is-on': i === 1 }" x-bind:aria-pressed="(i === 1).toString()" aria-label="Deposit" x-on:click="i = 1; stop = true">2</button>
                <button type="button" class="cc-story-dot" x-bind:class="{ 'is-on': i === 2 }" x-bind:aria-pressed="(i === 2).toString()" aria-label="Payouts" x-on:click="i = 2; stop = true">3</button>
            </div>
            <div class="cc-story-body">
                <span class="cc-story-tag" x-text="slides[i].tag">Ages {{ config('drinkwell.min_age') }} and over</span>
                <p class="cc-story-title" aria-live="polite" x-text="slides[i].title">Co-own a drink vending machine with a partner we find for you</p>
                @auth
                    <a class="af-btn cc-story-cta" href="{{ route('dashboard') }}">Open dashboard</a>
                @else
                    <a class="af-btn cc-story-cta" href="{{ route('register') }}">Get started</a>
                @endauth
                <a class="cc-story-link" x-bind:href="slides[i].link" x-text="slides[i].label" href="{{ route('program') }}">How it works</a>
            </div>
        </div>
    </section>

    <section class="cc-hero" data-bg="coral">
        <div class="cc-wrap cc-hero-grid">
            <div class="cc-hero-photo"
                x-data="{ i: 0, stop: false, slides: [
                    { tag: 'Ages {{ config('drinkwell.min_age') }} and over', title: 'Co-own a drink vending machine with a partner we find for you', link: '{{ route('program') }}', label: 'How it works' },
                    { tag: 'Deposit', title: 'Buy half of the machine from your co-partner', link: '{{ route('program') }}#deposit', label: 'How the deposit works' },
                    { tag: 'Payouts', title: 'Share the daily payout, with every cent shown', link: '{{ route('earnings') }}', label: 'See your earnings' }
                ] }"
                x-init="setInterval(() => { if (! stop && ! document.hidden) i = (i + 1) % 3 }, 6000)">
                <div class="cc-story-card">
                    <div class="cc-photo cc-story-photos" aria-hidden="true"><img src="{{ asset('images/home/story-1.jpg') }}" alt="" loading="lazy" decoding="async" x-bind:class="{ 'is-on': i === 0 }" class="is-on"><img src="{{ asset('images/home/story-2.jpg') }}" alt="" loading="lazy" decoding="async" x-bind:class="{ 'is-on': i === 1 }"><img src="{{ asset('images/home/story-3.jpg') }}" alt="" loading="lazy" decoding="async" x-bind:class="{ 'is-on': i === 2 }"></div>
                    <div class="cc-story-dots" x-bind:class="{ 'is-held': stop }" role="group" aria-label="Choose a story">
                        <button type="button" class="cc-story-dot" x-bind:class="{ 'is-on': i === 0 }" x-bind:aria-pressed="(i === 0).toString()" aria-label="Pairing" x-on:click="i = 0; stop = true">1</button>
                        <button type="button" class="cc-story-dot" x-bind:class="{ 'is-on': i === 1 }" x-bind:aria-pressed="(i === 1).toString()" aria-label="Deposit" x-on:click="i = 1; stop = true">2</button>
                        <button type="button" class="cc-story-dot" x-bind:class="{ 'is-on': i === 2 }" x-bind:aria-pressed="(i === 2).toString()" aria-label="Payouts" x-on:click="i = 2; stop = true">3</button>
                    </div>
                    <div class="cc-story-body">
                        <span class="cc-story-tag" x-text="slides[i].tag">Ages {{ config('drinkwell.min_age') }} and over</span>
                        <p class="cc-story-title" aria-live="polite" x-text="slides[i].title">Co-own a drink vending machine with a partner we find for you</p>
                        <a class="cc-story-link" x-bind:href="slides[i].link" x-text="slides[i].label" href="{{ route('program') }}">How it works</a>
                    </div>
                </div>
            </div>
            <div class="cc-hero-text">
                <h1 class="cc-display"><span>We Co-own</span><span>the Machine</span><span>and Share the</span><span class="cc-word" data-words="Daily Payout|Morning Rush|Lunch Peak">Daily Payout</span></h1>
                <p class="cc-lead">You and a partner each pay half the cost of a drink vending machine, and split what it earns every day. For people aged {{ config('drinkwell.min_age') }} and over.</p>
                <div class="af-ctas">
                    @auth
                        <a class="af-btn af-btn-black" href="{{ route('dashboard') }}">Open dashboard</a>
                    @else
                        <a class="af-btn af-btn-black" href="{{ route('register') }}">Get started</a>
                    @endauth
                    <a class="af-btn cc-btn-outline" href="{{ route('program') }}">How it works</a>
                </div>
            </div>
        </div>
    </section>

    <section class="cc-section cc-center cc-phone-left" data-bg="mint">
        <div class="cc-wrap">
            <p class="cc-statement">At {{ $brand->name }}, we are refreshing what it means to co&#8209;own: one drink, one payout at a time.</p>
            <p class="cc-lead" style="margin-inline:auto" data-reveal>You share the machine, the cost and the daily payout with one partner, and every cent is shown.</p>
            <div class="cc-btn-row" data-reveal><a class="af-btn af-btn-black" href="{{ route('program') }}">Discover how it works</a></div>
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="gray">
        <div class="cc-wrap">
            <h2 class="cc-huge" data-reveal><span>You don't run</span><span>the machine.</span><span>We do.</span></h2>
            <p class="cc-lead" style="margin-inline:auto" data-reveal>You never stock shelves or fix anything. Your part is the deposit, and then you watch the payouts.</p>
            <ol class="cc-cards3">
                <li class="cc-card cc-card-1" data-reveal><div class="cc-photo" aria-hidden="true"><img src="{{ asset('images/home/run-place.jpg') }}" alt="" loading="lazy" decoding="async"></div><div class="cc-card-body"><h3>We place it</h3><p>Your machine stands in a busy place that hosts it, like a hospital, a train station or an airport.</p></div></li>
                <li class="cc-card cc-card-2" data-reveal><div class="cc-photo" aria-hidden="true"><img src="{{ asset('images/home/run-restock.jpg') }}" alt="" loading="lazy" decoding="async"></div><div class="cc-card-body"><h3>We fill and restock it</h3><p>Filling, restocking and delivery are paid from the day's sales by the bottling and logistics fee.</p></div></li>
                <li class="cc-card cc-card-3" data-reveal><div class="cc-photo" aria-hidden="true"><img src="{{ asset('images/home/run-watch.jpg') }}" alt="" loading="lazy" decoding="async"></div><div class="cc-card-body"><h3>You watch it earn</h3><p>Your dashboard shows the machine, your balance and every daily payout, to the cent.</p></div></li>
            </ol>
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="gray">
        <div class="cc-wrap">
            <h2 class="cc-h2" data-reveal>Two machines, one 50/50 split</h2>
            <p class="cc-lead" style="margin-inline:auto" data-reveal>Every machine sells out every day. The platform fee is taken first, the rest is split in half.</p>
            @include('marketing.partials.options')
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="gray">
        <div class="cc-wrap">
            <h2 class="cc-h2" data-reveal>See what you could earn</h2>
            <p class="cc-lead" style="margin-inline:auto" data-reveal>Pick a machine and a number of days, and watch how fast the deposit comes back.</p>
            <div class="cc-btn-row" data-reveal><a class="af-btn af-btn-black" href="{{ route('earnings') }}">Your earnings</a></div>
        </div>
    </section>

    <section class="cc-section" data-bg="amber">
        <div class="cc-wrap">
            <h2 class="cc-huge" data-reveal><span>How you</span><span>get</span><span>paid</span></h2>
            <div class="cc-big-cards">
                <a href="{{ route('earnings') }}#split" class="cc-big-card cc-big-link" data-reveal><div class="cc-photo" aria-hidden="true"><img src="{{ asset('images/home/paid-daily.jpg') }}" alt="" loading="lazy" decoding="async"></div><div class="cc-big-body"><h3>How your daily payout is worked out</h3><p>The machine sells out, the fee comes off, and the rest is split in half. See the numbers.</p><span class="cc-big-arrow" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span></div></a>
                <a href="{{ route('earnings') }}#withdraw" class="cc-big-card cc-big-link" data-reveal><div class="cc-photo" aria-hidden="true"><img src="{{ asset('images/home/paid-withdraw.jpg') }}" alt="" loading="lazy" decoding="async"></div><div class="cc-big-body"><h3>Getting your money out</h3><p>Ask for {{ $fmt(config('drinkwell.withdrawal_min_cents')) }} or more, confirm with a code, and it is paid within {{ config('drinkwell.withdrawal_review_days') }} business days.</p><span class="cc-big-arrow" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span></div></a>
            </div>
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="cream">
        <div class="cc-wrap"><h2 class="cc-h2" data-reveal>Where your machine stands</h2><p class="cc-lead" style="margin-inline:auto" data-reveal>Hospitals, stations and airports across the United States and Canada.</p></div>
        <div class="cc-rows" aria-label="Facilities that host a machine">
            @foreach ([$facilities->take(ceil($facilities->count() / 2)), $facilities->slice(ceil($facilities->count() / 2))] as $r => $row)
                <div class="cc-row {{ $r ? 'cc-row-right' : '' }}">
                    <div class="cc-row-track">
                        @for ($n = 0; $n < 2; $n++)
                            @foreach ($row as $k => $name)
                                <a href="{{ route('locations') }}" @if ($n) aria-hidden="true" tabindex="-1" @endif><span class="cc-dot cc-dot-{{ ($k + $r) % 4 }}">{{ mb_substr($name, 0, 1) }}</span>{{ $name }}<span class="cc-explore">Explore</span></a>
                            @endforeach
                        @endfor
                    </div>
                </div>
            @endforeach
        </div>
        <div class="cc-btn-row"><a class="af-btn af-btn-black" href="{{ route('locations') }}">Meet the rest</a></div>
    </section>

    <section class="cc-section" data-bg="gray">
        <div class="cc-wrap">
            <h2 class="cc-huge" data-reveal><span>Start in</span><span>three steps</span></h2>
        </div>
        <div class="cc-tabs-row">
            <a class="cc-tabcard" href="{{ route('register') }}"><b class="cc-tab-num">1</b><p class="cc-tab-text">Sign up and we find you a co-partner, usually within 1 business day.</p><span>Pairing</span></a>
            <a class="cc-tabcard" href="{{ route('program') }}#deposit"><b class="cc-tab-num">2</b><p class="cc-tab-text">Pay your half to your co-partner, who already owns the machine. We check your proof of payment.</p><span>Deposit</span></a>
            <a class="cc-tabcard" href="{{ route('program') }}#split"><b class="cc-tab-num">3</b><p class="cc-tab-text">Every day the machine's earnings are split 50/50 and added to your balance.</p><span>Payouts</span></a>
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
                <form action="{{ route('search') }}" method="get" role="search">
                    <label class="sr-only" for="help-q">Search the site</label>
                    <input id="help-q" name="q" type="search" placeholder="Start typing here...">
                    <button class="af-btn af-btn-black" type="submit" aria-label="Search">Search</button>
                </form>
                <div class="cc-chips">
                    <a href="{{ route('program') }}">How payouts work</a>
                    <a href="{{ route('program') }}#deposit">How your deposit works</a>
                    <a href="{{ route('contact') }}">Contact us</a>
                    <a href="{{ route('terms') }}">Terms</a>
                    <a href="{{ route('privacy') }}">Privacy</a>
                </div>
            </div>
        </div>
    </section>
</x-marketing-layout>
