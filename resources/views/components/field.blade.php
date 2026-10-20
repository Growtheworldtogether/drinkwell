@props(['name', 'label', 'type' => 'text', 'help' => null, 'error' => null, 'value' => null])
@php
    $id = $attributes->get('id', 'field-'.str_replace(['[', ']', '.'], '-', $name));
    $message = $error ?? (isset($errors) ? ($errors->first($name) ?: null) : null);
    $describedBy = trim(($help ? $id.'-help ' : '').($message ? $id.'-error' : ''));
    $control = 'block w-full border bg-af-white px-3 text-body text-af-ink rounded-panel public:rounded-input '
        .($message ? 'border-af-danger' : 'border-af-300');
@endphp
@if ($type === 'checkbox')
    <div class="min-w-0">
        <label for="{{ $id }}" class="flex min-h-11 items-center gap-3 text-body">
            <input id="{{ $id }}" name="{{ $name }}" type="checkbox" value="{{ $value ?? 1 }}" {{ $attributes->except('id') }} @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
            <span>{{ $label }}</span>
        </label>
        @if ($help)
            <p id="{{ $id }}-help" class="text-body text-af-500">{{ $help }}</p>
        @endif
        @if ($message)
            <p id="{{ $id }}-error" class="mt-1 text-body text-af-danger">{{ $message }}</p>
        @endif
    </div>
@else
    <div class="min-w-0">
        <label for="{{ $id }}" class="mb-1 block text-label uppercase tracking-label text-af-500">{{ $label }}</label>
        @if ($type === 'textarea')
            <textarea id="{{ $id }}" name="{{ $name }}" rows="4" {{ $attributes->except('id')->class([$control, 'py-2']) }} @if ($message) aria-invalid="true" @endif @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>{{ $value ?? $slot }}</textarea>
        @elseif ($type === 'select')
            <select id="{{ $id }}" name="{{ $name }}" {{ $attributes->except('id')->class([$control, 'h-11']) }} @if ($message) aria-invalid="true" @endif @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>{{ $slot }}</select>
        @else
            <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" @if ($value !== null) value="{{ $value }}" @endif {{ $attributes->except('id')->class([$control, 'h-11']) }} @if ($message) aria-invalid="true" @endif @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
        @endif
        @if ($help)
            <p id="{{ $id }}-help" class="mt-1 text-body text-af-500">{{ $help }}</p>
        @endif
        @if ($message)
            <p id="{{ $id }}-error" class="mt-1 text-body text-af-danger">{{ $message }}</p>
        @endif
    </div>
@endif
