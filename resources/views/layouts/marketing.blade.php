<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head')
    @vite('resources/css/public.css')
</head>
<body class="cc-page font-sans antialiased">
    <canvas class="cc-mesh" data-mesh aria-hidden="true"></canvas>
    <div class="cc-grain" aria-hidden="true"></div>
    <div class="surface-public flex min-h-dvh flex-col text-body">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:bg-af-white focus:p-3">Skip to content</a>
        <x-site-header />
        <main id="main" class="min-w-0 flex-1">
            {{ $slot }}
        </main>
        <x-site-footer />
    </div>
    <button type="button" class="cc-access" data-text-size aria-label="Larger text">Aa</button>
    @livewireScriptConfig
</body>
</html>
