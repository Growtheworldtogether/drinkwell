@props(['groups'])
{{-- Nav groups for the rail and the sheet. The active item is the accent color on a soft fill. --}}
@foreach ($groups as $heading => $items)
    <div class="pf-nav-group">
        @unless (is_int($heading))
            <p class="pf-nav-head">{{ $heading }}</p>
        @endunless
        @foreach ($items as $item)
            @php([$route, $label] = $item)
            @php($count = $item[2] ?? null)
            @php($active = request()->routeIs($route, $route.'.*'))
            <a
                href="{{ route($route) }}"
                @if ($active) aria-current="page" @endif
                class="pf-nav-link {{ $active ? 'is-active' : '' }}"
            >{{ $label }}@if ($count) <span class="pf-count">{{ $count }}</span>@endif</a>
        @endforeach
    </div>
@endforeach
