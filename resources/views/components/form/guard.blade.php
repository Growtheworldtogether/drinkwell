@props(['model' => false])
{{-- Hidden honeypot field and a sealed start time (SPEC 17.2). People never see or fill it; bots do. --}}
<div class="sr-only" aria-hidden="true">
    <label>Leave this field empty
        <input type="text" name="{{ \App\Support\FormGuard::FIELD }}" value="" tabindex="-1" autocomplete="off" @if ($model) wire:model="guardWebsite" @endif>
    </label>
</div>
@unless ($model)
    <input type="hidden" name="{{ \App\Support\FormGuard::TOKEN }}" value="{{ \App\Support\FormGuard::token() }}">
@endunless
