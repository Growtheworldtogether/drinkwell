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
<header class="af-nav cc-nav">
    <div class="af-wrap"><div class="af-nav-in cc-nav-in">
        <a href="{{ url('/') }}" class="af-brand">
            <x-brand.logo variant="light" />
        </a>

        <nav aria-label="Primary" class="af-nav-links">
            @foreach ($links as [$route, $label])
                <div class="cc-drop">
                    <a href="{{ route($route) }}" class="af-nav-link" aria-haspopup="true" @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
                    <div class="cc-drop-panel">
                        <a href="{{ route('program') }}#split">The daily split</a>
                        <a href="{{ route('program') }}#day">A day in the life of a machine</a>
                        <a href="{{ route('program') }}#deposit">Paying the deposit</a>
                        <a href="{{ route('terms') }}">Terms</a>
                        <a href="{{ route('privacy') }}">Privacy</a>
                    </div>
                </div>
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
            <a href="{{ route('dashboard') }}" class="af-btn af-btn-black af-nav-cta">Dashboard</a>
        @else
            <a href="{{ route('register') }}" class="af-btn af-btn-black af-nav-cta">Get started</a>
        @endauth

        <x-menu-sheet tone="light">
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
    </div></div>
</header>
