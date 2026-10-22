@props(['mode' => 'member'])
@php
    $user = auth()->user();
    $sheetLink = 'cc-sheet-link';
    $links = $mode === 'admin'
        ? [['admin.overview', 'Overview'], ['admin.pairings', 'Pairings'], ['admin.withdrawals', 'Withdrawals'], ['admin.machines', 'Machines'], ['admin.settings', 'Settings'], ['admin.audit', 'Audit log']]
        : array_values(array_filter([
            $user?->role === \App\Enums\Role::Partner ? ['hub.dashboard', 'Dashboard'] : null,
            $user?->role === \App\Enums\Role::Partner ? ['hub.withdraw', 'Withdraw'] : null,
            $user?->role === \App\Enums\Role::Partner ? null : ['onboard.status', 'Your progress'],
            ['profile.edit', 'Profile'],
        ]));
@endphp
{{-- Signed-in header: same glass bar as the public pages, with the member or administrator links. --}}
<header class="cc-nav">
    <div class="cc-wrap cc-nav-in">
        <a href="{{ $user?->homeUrl() ?? url('/') }}" class="af-brand"><x-brand.logo variant="light" /></a>

        <nav aria-label="Primary" class="cc-nav-links">
            @foreach ($links as [$r, $label])
                <a href="{{ route($r) }}" class="af-nav-link" @if (request()->routeIs($r)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="af-nav-link">Sign out</button></form>
        </nav>

        <x-menu-sheet tone="light">
            @foreach ($links as [$r, $label])
                <a href="{{ route($r) }}" class="{{ $sheetLink }}">{{ $label }}</a>
            @endforeach
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="{{ $sheetLink }}">Sign out</button></form>
        </x-menu-sheet>
    </div>
</header>
