@php
    $sheetLink = 'flex min-h-12 items-center text-body text-af-ink hover:underline';
@endphp
{{-- Header: transparent over the hero, glass bar once scrolled. Below lg the links move into the Menu sheet. --}}
<header class="cc-nav">
    <div class="cc-wrap cc-nav-in">
        <a href="{{ url('/') }}" class="af-brand"><x-brand.logo variant="light" /></a>

        <nav aria-label="Primary" class="cc-nav-links">
            @foreach ([['program', 'How it works'], ['earnings', 'Your earnings'], ['locations', 'Locations'], ['questions', 'Questions']] as [$r, $label])
                <a href="{{ route($r) }}" class="af-nav-link" @if (request()->routeIs($r)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
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
            @foreach ([['program', 'How it works'], ['earnings', 'Your earnings'], ['locations', 'Locations'], ['questions', 'Questions']] as [$r, $label])
                <a href="{{ route($r) }}" class="{{ $sheetLink }}">{{ $label }}</a>
            @endforeach
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
