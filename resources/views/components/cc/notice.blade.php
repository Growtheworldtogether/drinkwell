@props(['type' => 'notice'])
<div role="{{ $type === 'error' ? 'alert' : 'status' }}" {{ $attributes->class(['cc-notice', 'is-error' => $type === 'error']) }}>{{ $slot }}</div>
