@props(['name', 'label', 'error' => null, 'value' => 1])
@php
    $id = $attributes->get('id', 'c-'.str_replace(['[', ']', '.'], '-', $name));
    $message = $error ?? (isset($errors) ? ($errors->first($name) ?: null) : null);
@endphp
<div>
    <label class="cc-check" for="{{ $id }}"><input id="{{ $id }}" name="{{ $name }}" type="checkbox" value="{{ $value }}" {{ $attributes->except('id') }}><span>{{ $label }}</span></label>
    @if ($message)<p class="cc-error" role="alert">{{ $message }}</p>@endif
</div>
