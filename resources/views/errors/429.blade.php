@php
    $wait = (int) ($exception->getHeaders()['Retry-After'] ?? 60);
    $text = $wait >= 120 ? ceil($wait / 60).' minutes' : ($wait === 1 ? '1 second' : $wait.' seconds');
@endphp
<x-marketing-layout title="Too many requests">
    <section class="af-band">
        <div class="af-wrap">
            <h1 class="af-h1">Please slow down</h1>
            <p class="af-lead">You have made too many requests in a short time. Wait about {{ $text }} and try again.</p>
            <p class="mt-6"><a class="af-link" href="{{ url('/') }}">Go to the home page</a></p>
        </div>
    </section>
</x-marketing-layout>
