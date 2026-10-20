@props(['trigger', 'title' => null, 'confirm' => 'Confirm', 'cancel' => 'Cancel', 'open' => false, 'triggerVariant' => 'destructive'])
{{-- Inline confirm panel under the action. No dialog, no scrim. Place inside the form that Confirm submits. --}}
<div x-data="{ open: @js((bool) $open) }" {{ $attributes->class(['min-w-0']) }}>
    <x-button :variant="$triggerVariant" x-show="! open" x-on:click="open = true; $nextTick(() => $refs.panel.focus())">{{ $trigger }}</x-button>

    <div x-show="open" x-cloak x-ref="panel" tabindex="-1" class="border border-af-ink bg-af-white p-4 rounded-panel">
        @if ($title)
            <h3 class="mb-3 text-h3">{{ $title }}</h3>
        @endif
        <div class="flex flex-col gap-4">
            {{ $slot }}
            <div class="flex flex-col gap-3 sm:flex-row">
                <x-button type="submit" variant="destructive">{{ $confirm }}</x-button>
                <x-button variant="secondary" x-on:click="open = false">{{ $cancel }}</x-button>
            </div>
        </div>
    </div>
</div>
