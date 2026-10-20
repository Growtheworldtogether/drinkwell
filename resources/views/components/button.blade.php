@props(['variant' => 'primary', 'href' => null, 'type' => 'button'])
@php
    $variants = [
        'primary' => 'border-af-accent bg-af-accent text-af-white hover:border-af-accent-ink hover:bg-af-accent-ink public:border-af-black public:bg-af-black public:hover:border-af-700 public:hover:bg-af-700 disabled:cursor-not-allowed disabled:border-af-300 disabled:bg-af-100 disabled:text-af-500 disabled:hover:border-af-300 disabled:hover:bg-af-100 public:disabled:border-af-300 public:disabled:bg-af-100 public:disabled:hover:border-af-300 public:disabled:hover:bg-af-100',
        'secondary' => 'border-af-300 bg-af-white text-af-ink hover:bg-af-100 public:border-af-black public:text-af-black disabled:cursor-not-allowed disabled:text-af-500',
        'destructive' => 'border-af-danger bg-af-white text-af-danger hover:bg-af-danger-tint disabled:cursor-not-allowed disabled:border-af-300 disabled:text-af-500',
        'tertiary' => 'border-transparent bg-transparent text-af-accent hover:underline public:text-af-ink disabled:cursor-not-allowed disabled:text-af-500',
        'inverse' => 'border-af-white bg-af-white text-af-black hover:border-af-100 hover:bg-af-100',
    ];
    $classes = 'inline-flex min-h-11 items-center justify-center gap-2 border px-4 text-center text-body font-medium rounded-panel lg:min-h-10 public:min-h-12 public:px-6 public:rounded-pill public:lg:min-h-12 '.($variants[$variant] ?? $variants['primary']);
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$classes]) }}>{{ $slot }}</button>
@endif
