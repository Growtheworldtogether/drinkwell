@php
    $sheetLink = 'flex min-h-12 items-center text-body text-af-ink hover:underline';
@endphp
{{-- Header: transparent over the hero, glass bar once scrolled. Below lg the links move into the Menu sheet. --}}
<header class="cc-nav">
    <div class="cc-wrap cc-nav-in">
        <a href="{{ url('/') }}" class="af-brand"><x-brand.logo variant="light" /></a>
        <span class="cc-chip-demo">Demo</span>

        <nav aria-label="Primary" class="cc-nav-links">
            <div class="cc-drop">
                <a href="{{ route('program') }}" class="af-nav-link" aria-haspopup="true" @if (request()->routeIs('program')) aria-current="page" @endif>How it works</a>
                <div class="cc-drop-panel">
                    <a href="{{ route('program') }}#split">The daily split</a>
                    <a href="{{ route('program') }}#day">A day in the life of a machine</a>
                    <a href="{{ route('program') }}#deposit">Paying the deposit</a>
                    <a href="{{ route('terms') }}">Terms</a>
                    <a href="{{ route('privacy') }}">Privacy</a>
                </div>
            </div>
            <a href="{{ url('/') }}#machines" class="af-nav-link">Machines</a>
            <a href="{{ url('/') }}#calculator" class="af-nav-link">Calculator</a>
            @guest
                <a href="{{ route('login') }}" class="af-nav-link">Sign in</a>
            @endguest
            @auth
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="af-nav-link">Sign out</button></form>
            @endauth
        </nav>

        @auth
            <a href="{{ route('dashboard') }}" class="af-btn af-btn-black af-nav-cta">Dashboard</a>
        @else
            <a href="{{ route('register') }}" class="af-btn af-btn-black af-nav-cta">Get started</a>
        @endauth

        <x-menu-sheet tone="light">
            <a href="{{ route('program') }}" class="{{ $sheetLink }}">How it works</a>
            <a href="{{ url('/') }}#machines" class="{{ $sheetLink }}">Machines</a>
            <a href="{{ url('/') }}#calculator" class="{{ $sheetLink }}">Calculator</a>
            @auth
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="{{ $sheetLink }} w-full text-left">Sign out</button></form>
                <a href="{{ route('dashboard') }}" class="af-btn af-btn-black mt-4">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="{{ $sheetLink }}">Sign in</a>
                <a href="{{ route('register') }}" class="af-btn af-btn-black mt-4">Get started</a>
            @endauth
        </x-menu-sheet>
    </div>
</header>
