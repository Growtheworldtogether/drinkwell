<nav class="cc-tabs" aria-label="Settings">
    <a href="{{ route('admin.settings') }}" @if (request()->routeIs('admin.settings')) aria-current="page" @endif>General</a>
    <a href="{{ route('admin.settings.branding') }}" @if (request()->routeIs('admin.settings.branding')) aria-current="page" @endif>Branding</a>
    <a href="{{ route('admin.withdrawals') }}">Withdrawals</a>
</nav>
