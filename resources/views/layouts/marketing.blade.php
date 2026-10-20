<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head')
</head>
<body class="bg-af-white text-af-ink font-sans antialiased">
    <div class="surface-public flex min-h-dvh flex-col text-body">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:bg-af-white focus:p-3">Skip to content</a>
            <x-site-header />
        <main id="main" class="min-w-0 flex-1">
            {{ $slot }}
        </main>
        <x-site-footer />
    </div>
    @livewireScriptConfig
</body>
</html>
