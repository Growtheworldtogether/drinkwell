@props(['variant' => 'band'])
@if ($variant === 'panel')
    {{-- Portal and admin telemetry panel --}}
    <section {{ $attributes->class(['min-w-0 border border-af-700 bg-af-dark p-4 text-af-white rounded-panel']) }}>
        {{ $slot }}
    </section>
@else
    {{-- Public full-width black band: 48px vertical padding on phones, 96px from lg --}}
    <section {{ $attributes->class(['bg-af-black text-af-white']) }}>
        <div class="mx-auto max-w-public px-4 py-12 sm:px-8 lg:py-24">
            {{ $slot }}
        </div>
    </section>
@endif
