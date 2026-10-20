@props(['numeric' => false])
<th scope="col" {{ $attributes->class([
    'border-b border-af-300 px-3 py-2 text-label uppercase tracking-label text-af-500',
    'text-right' => $numeric,
    'text-left' => ! $numeric,
]) }}>{{ $slot }}</th>
