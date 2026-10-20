@props(['columns' => 4])
{{-- KPI strip: one bordered grid, hairlines drawn by the gap. 2 by 2 below lg, one row from lg. --}}
<div {{ $attributes->class([
    'grid grid-cols-2 gap-px border border-af-200 bg-af-200 rounded-panel overflow-hidden',
    'lg:grid-cols-4' => (int) $columns === 4,
    'lg:grid-cols-3' => (int) $columns === 3,
]) }}>
    {{ $slot }}
</div>
