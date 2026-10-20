@props(['paginator'])
@php
    $simple = ! method_exists($paginator, 'lastPage');
    $current = $paginator->currentPage();
    $previous = $paginator->previousPageUrl();
    $next = $paginator->nextPageUrl();
@endphp
@if ($previous || $next || (! $simple && $paginator->lastPage() > 1))
    <nav aria-label="Pagination" {{ $attributes->class(['flex items-center justify-between gap-4']) }}>
        @if ($previous)
            <x-button :href="$previous" variant="secondary">Previous</x-button>
        @else
            <x-button variant="secondary" disabled>Previous</x-button>
        @endif

        <p class="font-mono text-body tabular-nums text-af-500">
            @if ($simple)
                page {{ $current }}
            @else
                page {{ $current }} of {{ $paginator->lastPage() }}
            @endif
        </p>

        @if ($next)
            <x-button :href="$next" variant="secondary">Next</x-button>
        @else
            <x-button variant="secondary" disabled>Next</x-button>
        @endif
    </nav>
@endif
