@props(['title', 'eyebrow' => null, 'lead' => null])
{{-- The top of every signed-in page: small label, big title, one plain sentence, and the main actions on the right. --}}
<header class="cc-phead">
    <div>
        @if ($eyebrow)<p class="cc-eyebrow">{{ $eyebrow }}</p>@endif
        <h1 class="cc-ptitle">{{ $title }}</h1>
        @if ($lead)<p class="cc-plead">{{ $lead }}</p>@endif
    </div>
    @if (trim((string) $slot) !== '')
        <div class="cc-pactions">{{ $slot }}</div>
    @endif
</header>
