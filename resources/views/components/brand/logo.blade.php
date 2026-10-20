@props(['variant' => 'light', 'size' => 'nav'])
@php
    $url = $brand->logoUrl($variant);
    $height = $size === 'auth' ? 32 : 24;
@endphp
@if ($url)
    <img src="{{ $url }}?v={{ $brand->version }}" alt="{{ $brand->name }}" height="{{ $height }}" {{ $attributes->class([$size === 'auth' ? 'h-8' : 'h-6', 'w-auto max-w-full']) }}>
@else
    <span {{ $attributes->class([
        'block min-w-0 font-semibold tracking-tight break-anywhere',
        $size === 'auth' ? 'text-h2' : 'text-h3',
        $variant === 'dark' ? 'text-af-white' : 'text-af-ink',
    ]) }}>{{ $brand->name }}</span>
@endif
