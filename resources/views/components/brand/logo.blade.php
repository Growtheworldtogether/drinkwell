@props(['variant' => 'light', 'size' => 'nav'])
@php
    $url = $brand->logoUrl($variant);
    // Grows with the screen: 36 px tall on a phone up to 48 px on a laptop (56 px on the sign-in pages).
    // Inline, so a new size needs no rebuilt stylesheet. The width follows the logo's shape, capped so a very wide logo still fits a phone.
    $height = $size === 'auth' ? 'clamp(44px, 1.8vw + 36px, 56px)' : 'clamp(36px, 1.4vw + 30px, 48px)';
@endphp
@if ($url)
    <img src="{{ $url }}?v={{ $brand->version }}" alt="{{ $brand->name }}" height="48" style="height:{{ $height }};width:auto;max-width:min(100%, 240px);object-fit:contain;object-position:left center" {{ $attributes }}>
@else
    <span {{ $attributes->class([
        'block min-w-0 font-semibold tracking-tight break-anywhere',
        $size === 'auth' ? 'text-h2' : 'text-h3',
        $variant === 'dark' ? 'text-af-white' : 'text-af-ink',
    ]) }}>{{ $brand->name }}</span>
@endif
