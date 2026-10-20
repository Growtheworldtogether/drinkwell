@props(['state'])
@php
    $states = [
        'online' => 'border-af-ok text-af-ok',
        'low_stock' => 'border-af-warn text-af-warn',
        'sold_out' => 'border-af-danger text-af-danger',
    ];
    $text = ucfirst(str_replace('_', ' ', (string) $state));
@endphp
{{-- Machine state: text plus a 1px border in the state color (SPEC 12.2). --}}
<span {{ $attributes->class(['inline-flex items-center border bg-af-white px-2 py-1 font-mono text-label rounded-panel public:rounded-pill', $states[$state] ?? $states['online']]) }}>{{ $text }}</span>
