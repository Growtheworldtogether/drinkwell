<footer class="cc-foot" data-bg="white">
    <div class="cc-wrap">
        <div class="cc-foot-brand"><x-brand.logo variant="light" /></div>
        <div class="cc-foot-cols">
            <div>
                <h4>About us</h4>
                <a href="{{ route('program') }}">How it works</a>
                <a href="{{ url('/') }}#machines">Machines</a>
                <a href="{{ url('/') }}#calculator">Calculator</a>
            </div>
            <div>
                <h4>Need help?</h4>
                <a href="mailto:{{ $brand->supportEmail }}" class="break-anywhere">{{ $brand->supportEmail }}</a>
                <a href="{{ route('program') }}#deposit">Paying the deposit</a>
            </div>
            <div>
                <h4>Useful links</h4>
                <a href="{{ route('terms') }}">Terms</a>
                <a href="{{ route('privacy') }}">Privacy</a>
                @guest
                    <a href="{{ route('login') }}">Sign in</a>
                @endguest
                @auth
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Sign out</button></form>
                @endauth
            </div>
        </div>
        <div class="cc-foot-legal">
            <p>{{ $brand->name }}</p>
            <x-footer-disclaimer />
        </div>
    </div>
</footer>
