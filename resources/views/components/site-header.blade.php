@php
    $links = collect([
        ['program', 'How it works'],
    ])->filter(fn ($link) => Route::has($link[0]));
    $sheetLinks = collect([
        ['program', 'How it works'],
    ])->filter(fn ($link) => Route::has($link[0]));
    $sheetLink = 'flex min-h-12 items-center text-body text-af-white hover:underline';
@endphp
{{-- v9 header: black bar, brand left, Program, Territories, Specs, Questions, Sign in, one white pill. Below lg the links move into the Menu sheet. --}}
<header class="af-nav">
    <div class="af-wrap af-nav-in">
        <a href="{{ url('/') }}" class="af-brand">
            <x-brand.logo variant="dark" />
        </a>

        <nav aria-label="Primary" class="af-nav-links">
            @foreach ($links as [$route, $label])
                <a href="{{ route($route) }}" class="af-nav-link" @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
            @guest
                <a href="{{ route('login') }}" class="af-nav-link">Sign in</a>
            @endguest
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="af-nav-link">Sign out</button>
                </form>
            @endauth
        </nav>

        @auth
            <a href="{{ route('dashboard') }}" class="af-btn af-btn-light af-nav-cta">Dashboard</a>
        @else
            <a href="{{ route('register') }}" class="af-btn af-btn-light af-nav-cta">Get started</a>
        @endauth

        <x-menu-sheet tone="dark">
            @foreach ($sheetLinks as [$route, $label])
                <a href="{{ route($route) }}" class="{{ $sheetLink }}">{{ $label }}</a>
            @endforeach
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="{{ $sheetLink }} w-full text-left">Sign out</button>
                </form>
                <a href="{{ route('dashboard') }}" class="af-btn af-btn-light mt-4">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="{{ $sheetLink }}">Sign in</a>
                <a href="{{ route('register') }}" class="af-btn af-btn-light mt-4">Get started</a>
            @endauth
        </x-menu-sheet>
    </div>
</header>
