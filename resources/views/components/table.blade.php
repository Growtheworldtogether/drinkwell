@props(['caption' => null])
{{--
    One table for every screen (SPEC 12.6). Give every <x-table.td> a label.
    Below sm: stacked cards of label/value pairs. sm to lg: scrolls inside this wrapper, first column sticky.
    From lg: a normal table.
--}}
<div {{ $attributes->class(['min-w-0 sm:overflow-x-auto']) }}>
    <table class="af-table text-body">
        @if ($caption)
            <caption class="sr-only">{{ $caption }}</caption>
        @endif
        <thead>
            <tr>
                {{ $head }}
            </tr>
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
