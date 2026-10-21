@props(['paginator'])
@php
    $simple = ! method_exists($paginator, 'lastPage');
    $previous = $paginator->previousPageUrl();
    $next = $paginator->nextPageUrl();
@endphp
@if ($previous || $next || (! $simple && $paginator->lastPage() > 1))
    <nav aria-label="Pagination" {{ $attributes->class(['cc-pager']) }}>
        @if ($previous)<a class="af-btn cc-btn-outline" href="{{ $previous }}">Previous</a>@else<span class="af-btn cc-btn-outline is-off" aria-disabled="true">Previous</span>@endif
        <p>Page {{ $paginator->currentPage() }}@unless ($simple) of {{ $paginator->lastPage() }}@endunless</p>
        @if ($next)<a class="af-btn cc-btn-outline" href="{{ $next }}">Next</a>@else<span class="af-btn cc-btn-outline is-off" aria-disabled="true">Next</span>@endif
    </nav>
@endif
