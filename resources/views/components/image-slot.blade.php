@props(['subject', 'ratio' => '16:9', 'src' => null, 'alt' => null])
@php
    $aspect = $ratio === '4:3' ? 'aspect-4/3' : 'aspect-video';
@endphp
@if ($src)
    <figure {{ $attributes->class(['w-full overflow-hidden border border-af-200', $aspect]) }}>
        <img src="{{ $src }}" alt="{{ $alt }}" class="h-full w-full max-w-full object-cover">
    </figure>
@else
    {{-- Labeled slot until the project owner supplies the photo. No placeholder art. --}}
    <div {{ $attributes->class(['flex w-full items-center justify-center border border-af-200 p-4', $aspect]) }} role="img" aria-label="Placeholder for {{ $subject }}">
        <p class="text-center font-mono text-label uppercase tracking-label text-af-500 break-anywhere">IMAGE SLOT {{ $ratio }} / {{ $subject }}</p>
    </div>
@endif
