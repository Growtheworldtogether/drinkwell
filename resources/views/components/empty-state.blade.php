@props(['title', 'href' => null, 'action' => null])
{{-- An empty list: what is missing, why, and what happens next. One optional next step. --}}
<div class="pf-empty">
    <p class="pf-empty-title">{{ $title }}</p>
    <p class="pf-text">{{ $slot }}</p>
    @if ($href && $action)
        <a href="{{ $href }}" class="pf-link">{{ $action }}</a>
    @endif
</div>
