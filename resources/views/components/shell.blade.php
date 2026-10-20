@props(['groups', 'title' => null, 'fluid' => false, 'actions' => null])
@php
    // Only destinations whose route exists are listed, so the rail grows as screens ship.
    $groups = collect($groups)
        ->map(fn ($items) => collect($items)->filter(fn ($item) => Route::has($item[0])))
        ->filter(fn ($items) => $items->isNotEmpty());
    $user = auth()->user();
@endphp
{{-- Portal and admin shell (SPEC 12.6). From lg: 208 px rail and a 56 px top bar with the page title and its actions. Below lg: a top bar with a Menu button and a full-screen sheet. --}}
<div class="surface-portal pf-app">
    <aside class="pf-rail">
        <a href="{{ url('/') }}" class="pf-brand"><x-brand.logo /></a>
        <nav aria-label="Primary" class="pf-nav">
            <x-shell-nav :groups="$groups" />
        </nav>
        @if ($user)
            <div class="pf-user">
                <p class="pf-user-name">{{ $user->name }}</p>
                <p class="pf-user-mail">{!! str_replace('@', '<wbr>@', e($user->email)) !!}</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="pf-signout">Sign out</button>
                </form>
            </div>
        @endif
    </aside>

    <div class="pf-col">
        <header class="pf-bar-sm">
            <a href="{{ url('/') }}" class="pf-brand-sm"><x-brand.logo /></a>
            <p class="pf-bar-title">{{ $title }}</p>
            <x-menu-sheet>
                <x-shell-nav :groups="$groups" />
                @if ($user)
                    <div class="mt-6 border-t border-af-200 pt-4">
                        <p class="break-anywhere text-body font-medium">{{ $user->name }}</p>
                        <p class="mb-3 break-anywhere text-body text-af-500">{{ $user->email }}</p>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-button type="submit" variant="secondary" class="w-full">Sign out</x-button>
                        </form>
                    </div>
                @endif
            </x-menu-sheet>
        </header>

        <div class="pf-bar">
            <h1 class="pf-bar-h1">{{ $title }}</h1>
            @if ($actions)
                <div class="pf-bar-actions">{{ $actions }}</div>
            @endif
        </div>

        <main id="main" class="pf-main {{ $fluid ? 'pf-main-fluid' : '' }}">
            @if ($title)
                <h1 class="sr-only lg:hidden">{{ $title }}</h1>
            @endif
            {{ $slot }}
        </main>

        <footer class="pf-foot">
            <x-footer-disclaimer class="text-af-500" />
        </footer>
    </div>
</div>
