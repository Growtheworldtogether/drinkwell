@props(['label' => '', 'numeric' => false, 'actions' => false])
{{-- label becomes data-label, shown as the key in the stacked card layout. Actions cells carry no label. --}}
<td data-label="{{ $label }}" @if ($actions) data-actions @endif {{ $attributes->class([
    'border-b border-af-200 px-3 py-2 text-body',
    'text-right font-mono tabular-nums' => $numeric,
]) }}>{{ $slot }}</td>
