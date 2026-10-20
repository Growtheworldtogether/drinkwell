@props(['label', 'value' => null, 'cents' => null, 'note' => null])
{{-- One cell of a KPI strip. Value is Geist Mono; pass cents for money. --}}
<div {{ $attributes->class(['min-w-0 bg-af-white p-4']) }}>
    <p class="text-label uppercase tracking-label text-af-500">{{ $label }}</p>
    <p class="mt-2 break-anywhere font-mono tabular-nums text-h1 font-medium text-af-ink">
        @if ($cents !== null)
            <x-money :cents="$cents" />
        @else
            {{ $value ?? $slot }}
        @endif
    </p>
    @if ($note)
        <p class="mt-1 text-body text-af-500">{{ $note }}</p>
    @endif
</div>
