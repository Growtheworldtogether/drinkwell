<footer class="cc-foot" data-bg="white">
    <div class="cc-wrap">
        <div class="cc-foot-brand"><x-brand.logo variant="light" /></div>
        <div class="cc-foot-cols">
            <div>
                <h4>About us</h4>
                <a href="{{ route('program') }}">How it works</a>
                <a href="{{ route('earnings') }}">Your earnings</a>
                <a href="{{ route('locations') }}">Locations</a>
            </div>
            <div>
                <h4>Need help?</h4>
                <a href="mailto:{{ $brand->supportEmail }}" class="cc-email">{!! str_replace('@', '@<wbr>', e($brand->supportEmail)) !!}</a>
                <a href="{{ route('questions') }}">Questions</a>
                <a href="{{ route('program') }}#deposit">How your deposit works</a>
                <a href="{{ route('contact') }}">Contact us</a>
            </div>
            <div>
                <h4>Useful links</h4>
                <a href="{{ route('terms') }}">Terms</a>
                <a href="{{ route('privacy') }}">Privacy</a>
                <button type="button" data-text-size aria-pressed="false"><span data-text-size-label>Bigger text</span></button>
                @guest
                    <a href="{{ route('login') }}">Sign in</a>
                @endguest
                @auth
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Sign out</button></form>
                @endauth
            </div>
        </div>
    </div>
    <div class="cc-foot-base">
        <div class="cc-foot-clouds" aria-hidden="true"><i></i></div>
        <div class="cc-foot-legal-bar">
            <div class="cc-wrap cc-foot-legal">
                <p>{{ $brand->name }}</p>
                <x-footer-disclaimer />
            </div>
        </div>
    </div>
</footer>
