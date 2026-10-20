@props(['tone' => 'light', 'label' => 'Menu'])
@php
    $dark = $tone === 'dark';
    $sheetId = 'sheet-'.\Illuminate\Support\Str::random(6);
    $buttonBase = 'inline-flex min-h-11 items-center justify-center border px-4 font-medium rounded-panel public:rounded-pill';
@endphp
{{-- Below lg only. A solid full-screen sheet: Escape closes it and focus returns to the Menu button. --}}
<div
    x-data="{ open: false }"
    x-on:keydown.escape.window="if (open) { open = false; $nextTick(() => $refs.trigger.focus()) }"
    x-effect="document.body.classList.toggle('overflow-hidden', open)"
    class="lg:hidden"
>
    <button
        type="button"
        x-ref="trigger"
        x-on:click="open = true; $nextTick(() => $refs.close.focus())"
        x-bind:aria-expanded="open.toString()"
        aria-controls="{{ $sheetId }}"
        class="{{ $buttonBase }} {{ $dark ? 'border-af-700 text-af-white hover:bg-af-700' : 'border-af-300 text-af-ink hover:bg-af-100' }}"
    >{{ $label }}</button>

    <div
        id="{{ $sheetId }}"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex flex-col overflow-y-auto pt-safe pb-safe pl-safe pr-safe {{ $dark ? 'bg-af-black text-af-white' : 'bg-af-white text-af-ink' }}"
    >
        <div class="flex h-14 shrink-0 items-center justify-between gap-4 px-4">
            <x-brand.logo :variant="$dark ? 'dark' : 'light'" />
            <button
                type="button"
                x-ref="close"
                x-on:click="open = false; $nextTick(() => $refs.trigger.focus())"
                class="{{ $buttonBase }} {{ $dark ? 'border-af-700 text-af-white hover:bg-af-700' : 'border-af-300 text-af-ink hover:bg-af-100' }}"
            >Close</button>
        </div>
        <nav aria-label="Menu" class="flex flex-col px-4 py-4">
            {{ $slot }}
        </nav>
    </div>
</div>
