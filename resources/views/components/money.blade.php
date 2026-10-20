@props(['cents'])
<span {{ $attributes->class(['font-mono tabular-nums font-medium']) }}>{{ \App\Support\Money::format((int) $cents) }}</span>
