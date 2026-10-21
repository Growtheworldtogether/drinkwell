@props(['name', 'label', 'type' => 'text', 'help' => null, 'error' => null, 'value' => null])
@php
    $id = $attributes->get('id', 'f-'.str_replace(['[', ']', '.'], '-', $name));
    $message = $error ?? (isset($errors) ? ($errors->first($name) ?: null) : null);
    $describedBy = trim(($help ? $id.'-help ' : '').($message ? $id.'-error' : ''));
@endphp
<div class="cc-field">
    <label for="{{ $id }}">{{ $label }}</label>
    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="5" {{ $attributes->except('id')->class(['is-bad' => $message]) }} @if ($message) aria-invalid="true" @endif @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>{{ $value ?? $slot }}</textarea>
    @elseif ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" {{ $attributes->except('id')->class(['is-bad' => $message]) }} @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>{{ $slot }}</select>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" @if ($value !== null) value="{{ $value }}" @endif {{ $attributes->except('id')->class(['is-bad' => $message]) }} @if ($message) aria-invalid="true" @endif @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
    @endif
    @if ($help)<p id="{{ $id }}-help" class="cc-hint">{{ $help }}</p>@endif
    @if ($message)<p id="{{ $id }}-error" class="cc-error" role="alert">{{ $message }}</p>@endif
</div>
