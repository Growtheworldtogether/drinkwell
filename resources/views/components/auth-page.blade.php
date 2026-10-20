@props(['title', 'intro' => null])
{{-- Public surface wrapper for every auth screen: one column, max-w-xl. --}}
<x-marketing-layout :title="$title">
    <section class="af-band">
        <div class="af-wrap af-auth">
            <x-brand.logo size="auth" />
            <h1 class="af-h1 mt-6">{{ $title }}</h1>
            @if ($intro)
                <p class="af-lead mt-3">{{ $intro }}</p>
            @endif
            @if (is_string(session('status')))
                <x-banner class="mt-6">{{ session('status') }}</x-banner>
            @endif
            <div class="mt-8">
                {{ $slot }}
            </div>
        </div>
    </section>
</x-marketing-layout>
