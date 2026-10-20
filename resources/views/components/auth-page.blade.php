@props(['title', 'intro' => null, 'bg' => 'coral'])
{{-- Sign-in family: one frosted card on the animated gradient. --}}
<x-marketing-layout :title="$title">
    <section class="cc-screen cc-auth" data-bg="{{ $bg }}">
        <div class="cc-wrap" style="display:grid;place-items:center">
            <div class="cc-auth-card">
                <h1>{{ $title }}</h1>
                @if ($intro)
                    <p class="cc-sub">{{ $intro }}</p>
                @endif
                @if (is_string(session('status')))
                    <x-cc.notice>{{ session('status') }}</x-cc.notice>
                @endif
                {{ $slot }}
            </div>
        </div>
    </section>
</x-marketing-layout>
