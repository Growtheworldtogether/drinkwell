@php
    $fmt = fn (int $c) => \App\Support\Money::format($c);
    $me = \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    $lines = [
        'Looking for members who chose the '.$tier->label(),
        'Checking machines with a free half',
        'Matching your '.$fmt($tier->stakeCents()).' share',
        'Co-partner found',
    ];
@endphp
{{-- Automatic pairing: a short search on screen, then the form below asks the server to pair (OnboardController::match). --}}
<style>
    .dw-search { display: grid; justify-items: center; gap: 18px; text-align: center; }
    .dw-radar { position: relative; width: min(260px, 62vw); aspect-ratio: 1; display: grid; place-items: center; }
    .dw-ring { position: absolute; inset: 0; border: 2px solid currentColor; border-radius: 50%; opacity: 0; animation: dw-ring 2.4s ease-out infinite; }
    .dw-ring:nth-child(2) { animation-delay: 0.8s; }
    .dw-ring:nth-child(3) { animation-delay: 1.6s; }
    @keyframes dw-ring { from { transform: scale(0.3); opacity: 0.55; } to { transform: scale(1); opacity: 0; } }
    .dw-me { position: relative; z-index: 1; width: 84px; height: 84px; font-size: 28px; }
    .dw-face { position: absolute; z-index: 1; width: 52px; height: 52px; border-radius: 50%; object-fit: cover; border: 3px solid white; opacity: 0; transform: scale(0.4); transition: opacity 0.4s ease, transform 0.4s cubic-bezier(.2, 1.4, .4, 1); }
    .dw-face.is-on { opacity: 1; transform: none; }
    .dw-f1 { top: 6%; left: 14%; } .dw-f2 { top: 18%; right: 4%; } .dw-f3 { bottom: 6%; left: 30%; }
    .dw-radar.is-found .dw-ring { animation-play-state: paused; }
    .dw-line { min-height: 1.5em; margin: 0; font-size: clamp(18px, 1vw + 14px, 22px); font-weight: 700; }
    .dw-dots::after { content: '...'; display: inline-block; white-space: nowrap; width: 1.2em; text-align: left; animation: dw-dots 1.2s steps(4) infinite; overflow: hidden; vertical-align: bottom; }
    @keyframes dw-dots { from { width: 0; } to { width: 1.2em; } }
    .dw-bar { width: min(360px, 100%); height: 8px; border-radius: 9999px; background: rgba(0, 0, 0, 0.12); overflow: hidden; }
    .dw-bar i { display: block; height: 100%; width: 8%; border-radius: inherit; background: black; transition: width 1.2s ease; }
    .dw-search .cc-dl { width: 100%; text-align: left; }
    @media (prefers-reduced-motion: reduce) { .dw-ring, .dw-dots::after { animation: none; } .dw-ring { opacity: 0.25; transform: scale(0.8); } .dw-face { transition: none; } }
</style>

<x-cc.head title="Looking for your co-partner" eyebrow="Your request" lead="Please keep this page open. It only takes a moment." />

<div class="cc-panel dw-search"
     x-data="{ step: 0, late: false, lines: @js($lines) }"
     x-init="const slow = ! window.matchMedia('(prefers-reduced-motion: reduce)').matches; const gap = slow ? 1700 : 700;
             const tick = () => { if (step < lines.length - 1) { step++; setTimeout(tick, gap); } else { setTimeout(() => $refs.go.submit(), slow ? 1200 : 400); } };
             setTimeout(tick, gap); setTimeout(() => late = true, 12000);">
    <div class="dw-radar" :class="{ 'is-found': step === lines.length - 1 }" aria-hidden="true">
        <span class="dw-ring"></span><span class="dw-ring"></span><span class="dw-ring"></span>
        <span class="cc-avatar dw-me">{{ $me }}</span>
        <img class="dw-face dw-f1" :class="{ 'is-on': step >= 0 }" src="{{ asset('images/home/story-1-thumb.jpg') }}" alt="">
        <img class="dw-face dw-f2" :class="{ 'is-on': step >= 1 }" src="{{ asset('images/home/story-2-thumb.jpg') }}" alt="">
        <img class="dw-face dw-f3" :class="{ 'is-on': step >= 2 }" src="{{ asset('images/home/story-3-thumb.jpg') }}" alt="">
    </div>

    <p class="dw-line" role="status" aria-live="polite"><span x-text="lines[step]">{{ $lines[0] }}</span><span class="dw-dots" x-show="step < lines.length - 1"></span></p>
    <div class="dw-bar" aria-hidden="true"><i :style="'width:' + Math.round((step + 1) / lines.length * 100) + '%'"></i></div>

    <form x-ref="go" method="POST" action="{{ route('onboard.match') }}">
        @csrf
        <button type="submit" class="af-btn af-btn-black" style="width:auto" x-show="late" x-cloak>Show my co-partner</button>
        <noscript><button type="submit" class="af-btn af-btn-black" style="width:auto">Show my co-partner</button></noscript>
    </form>

    <dl class="cc-dl">
        <div class="cc-kv"><dt>Option</dt><dd>{{ $tier->label() }}</dd></div>
        <div class="cc-kv"><dt>Your deposit</dt><dd>{{ $fmt($tier->stakeCents()) }}</dd></div>
    </dl>
</div>
@include('onboard._progress', ['stage' => 1])
