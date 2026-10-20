@props(['name' => 'code', 'label' => 'Code', 'error' => null])
@php
    $message = $error ?? (isset($errors) ? ($errors->first($name) ?: null) : null);
@endphp
{{-- Six separate mono boxes. Auto-advance, backspace moves back, paste fills all six. The joined value posts as one field. --}}
<div
    class="cc-field"
    x-data="{
        digits: ['', '', '', '', '', ''],
        focusBox(i) { this.$refs['box' + i]?.focus(); },
        typed(i, event) {
            const value = event.target.value.replace(/\D/g, '').slice(-1);
            event.target.value = value;
            this.digits[i] = value;
            if (value && i < 5) this.focusBox(i + 1);
        },
        back(i, event) {
            if (event.key === 'Backspace' && ! this.digits[i] && i > 0) this.focusBox(i - 1);
        },
        paste(event) {
            const text = (event.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
            if (! text) return;
            event.preventDefault();
            this.digits = Array.from({ length: 6 }, (_, i) => text[i] ?? '');
            this.digits.forEach((d, i) => { this.$refs['box' + i].value = d; });
            this.focusBox(Math.min(text.length, 5));
        },
    }"
    x-on:paste="paste($event)"
>
    <label for="{{ $name }}-0">{{ $label }}</label>
    <input type="hidden" name="{{ $name }}" x-bind:value="digits.join('')">
    <div class="cc-otp" role="group" aria-label="{{ $label }}, six digits">
        @for ($i = 0; $i < 6; $i++)
            <input
                id="{{ $name }}-{{ $i }}"
                x-ref="box{{ $i }}"
                type="text"
                inputmode="numeric"
                pattern="[0-9]"
                maxlength="1"
                autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                aria-label="Digit {{ $i + 1 }}"
                @if ($message) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
                x-on:input="typed({{ $i }}, $event)"
                x-on:keydown="back({{ $i }}, $event)"
            >
        @endfor
    </div>
    @if ($message)
        <p id="{{ $name }}-error" class="cc-error" role="alert">{{ $message }}</p>
    @endif
</div>
