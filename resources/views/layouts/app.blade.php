@php $mode = $mode ?? 'member'; $bg = $bg ?? 'gray'; @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head')
    @vite('resources/css/public.css')
</head>
<body class="cc-page font-sans antialiased {{ $mode === 'focus' ? '' : 'cc-app' }}">
    <canvas class="cc-mesh" data-mesh aria-hidden="true"></canvas>
    <div class="cc-grain" aria-hidden="true"></div>
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:bg-af-white focus:p-3">Skip to content</a>
    @if ($mode === 'focus')
        <div class="surface-public flex min-h-dvh flex-col text-body">
            <x-member-header mode="member" />
            <main id="main" class="min-w-0 flex-1">{{ $slot }}</main>
            <x-site-footer />
        </div>
    @else
        <div class="cc-shell" x-data="{ nav: false }" x-on:keydown.escape.window="nav = false" x-effect="document.body.classList.toggle('cc-noscroll', nav)">
            <div class="cc-topbar">
                <a href="{{ auth()->user()?->homeUrl() ?? url('/') }}" class="af-brand"><x-brand.logo variant="light" /></a>
                <button type="button" class="cc-menu-btn" x-on:click="nav = true" x-bind:aria-expanded="nav.toString()" aria-controls="app-side">Menu</button>
            </div>
            <div class="cc-scrim" x-show="nav" x-cloak x-on:click="nav = false" aria-hidden="true"></div>
            <x-app-sidebar :mode="$mode" />
            <div class="cc-main">
                <main id="main" class="cc-content" data-bg="{{ $bg }}">
                    {{ $slot }}
                </main>
                <footer class="cc-appfoot">
                    <p>{{ $brand->name }} · <a href="{{ route('contact') }}">Contact us</a> · <a href="{{ route('terms') }}">Terms</a> · <a href="{{ route('privacy') }}">Privacy</a></p>
                    <x-footer-disclaimer />
                </footer>
            </div>
        </div>
    @endif
    <button type="button" class="cc-access" data-text-size aria-pressed="false"><b aria-hidden="true">Aa</b><span data-text-size-label>Bigger text</span></button>
    @livewireScriptConfig
</body>
</html>
