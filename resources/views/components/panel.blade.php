@props(['title' => null])
<section {{ $attributes->class(['min-w-0 border border-af-200 bg-af-white p-4 rounded-panel']) }}>
    @if ($title)
        <h2 class="mb-3 text-label uppercase tracking-label text-af-500">{{ $title }}</h2>
    @endif
    {{ $slot }}
</section>
