@props(['links' => []])
{{-- "You may also like": two big photo teasers that lead to other pages. Each link is [route name, heading]. --}}
<section class="cc-section" data-bg="gray">
    <div class="cc-wrap">
        <h2 class="cc-h2 cc-center" data-reveal>You may also like</h2>
        <div class="cc-big-cards">
            @foreach ($links as [$route, $label])
                <a href="{{ route($route) }}" class="cc-big-card cc-big-link" data-reveal><div class="cc-photo" aria-hidden="true"><span>Photo</span></div><h3>{{ $label }}</h3></a>
            @endforeach
        </div>
    </div>
</section>
