@props(['type' => 'notice'])
@php
    $prefix = ['notice' => 'Notice', 'warning' => 'Warning', 'error' => 'Error', 'success' => 'Done'][$type] ?? 'Notice';
@endphp
{{-- Severity is carried by the text prefix, not by color. --}}
<div role="{{ in_array($type, ['warning', 'error'], true) ? 'alert' : 'status' }}" {{ $attributes->class(['min-w-0 border border-af-300 bg-af-white p-3 text-body break-words portal:rounded-panel']) }}>
    <strong class="font-semibold">{{ $prefix }}.</strong> {{ $slot }}
</div>
