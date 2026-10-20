@props(['status', 'tone' => null, 'label' => null])
@php
    $tones = [
        'ok' => 'border-af-ok bg-af-ok-tint text-af-ok',
        'warn' => 'border-af-warn bg-af-warn-tint text-af-warn',
        'danger' => 'border-af-danger bg-af-danger-tint text-af-danger',
        'neutral' => 'border-af-500 bg-af-white text-af-500',
    ];
    $byStatus = [
        'approved' => 'ok', 'confirmed' => 'ok', 'settled' => 'ok', 'funded' => 'ok', 'paid' => 'ok', 'active' => 'ok', 'posted' => 'ok',
        'pending' => 'warn', 'submitted' => 'warn', 'awaiting_review' => 'warn', 'awaiting_funding' => 'warn', 'low' => 'warn',
        'rejected' => 'danger', 'failed' => 'danger', 'critical' => 'danger', 'disabled' => 'danger',
    ];
    $key = $tone ?? ($byStatus[$status] ?? 'neutral');
    $text = $label ?? ucfirst(str_replace('_', ' ', (string) $status));
@endphp
{{-- Bordered text label. No swatch, no icon. --}}
<span {{ $attributes->class(['inline-flex items-center border px-2 py-1 font-mono text-label rounded-panel public:rounded-pill', $tones[$key] ?? $tones['neutral']]) }}>{{ $text }}</span>
