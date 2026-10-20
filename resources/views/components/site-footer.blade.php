@php
    $links = collect([
        ['program', 'How it works'],
        ['terms', 'Terms'],
        ['privacy', 'Privacy'],
    ])->filter(fn ($link) => Route::has($link[0]));
@endphp
{{-- v9 footer: links, then the support contact, then the Demo mark at the right end of its own row. Support contact comes from the branding settings. --}}
<footer class="af-foot cc-foot">
    <div class="af-wrap">
        @foreach ($links as [$route, $label])
            <a href="{{ route($route) }}">{{ $label }}</a>
        @endforeach
        @guest
            <a href="{{ route('login') }}">Sign in</a>
        @endguest
        @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Sign out</button>
            </form>
        @endauth
        <p class="af-foot-contact">Support: <a href="mailto:{{ $brand->supportEmail }}" class="break-anywhere">{{ $brand->supportEmail }}</a></p>
        <x-footer-disclaimer class="af-foot-note" />
    </div>
</footer>
