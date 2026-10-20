@php
    $steps = ['Finding a partner', 'Paired', 'You sent it', 'Deposit confirmed'];
@endphp
<ol class="cc-pill-steps" aria-label="Progress">
    @foreach ($steps as $i => $label)
        <li @class(['is-done' => $i + 1 < $stage, 'is-now' => $i + 1 === $stage]) @if ($i + 1 === $stage) aria-current="step" @endif><span>{{ $i + 1 }}</span>{{ $label }}</li>
    @endforeach
</ol>
