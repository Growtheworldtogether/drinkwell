@props(['tone' => 'light', 'label' => 'Menu'])
@php $sheetId = 'sheet-'.\Illuminate\Support\Str::random(6); @endphp
{{-- Below lg only. A full-screen sheet styled in public.css; Escape closes it and focus returns to the Menu button. --}}
<div
    class="cc-sheet-wrap"
    x-data="{ open: false }"
    x-on:keydown.escape.window="if (open) { open = false; $nextTick(() => $refs.trigger.focus()) }"
    x-effect="document.body.classList.toggle('cc-noscroll', open)"
>
    <button type="button" x-ref="trigger" class="cc-menu-btn" x-on:click="open = true; $nextTick(() => $refs.close.focus())" x-bind:aria-expanded="open.toString()" aria-controls="{{ $sheetId }}">{{ $label }}</button>

    <div id="{{ $sheetId }}" class="cc-sheet" x-show="open" x-cloak role="dialog" aria-modal="true" aria-label="Menu">
        <div class="cc-sheet-top">
            <x-brand.logo variant="light" />
            <button type="button" x-ref="close" class="cc-menu-btn" x-on:click="open = false; $nextTick(() => $refs.trigger.focus())">Close</button>
        </div>
        <nav aria-label="Menu" class="cc-sheet-nav">
            {{ $slot }}
        </nav>
    </div>
</div>
