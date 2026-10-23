<nav class="cc-tabs" aria-label="Settings">
    <a href="{{ route('admin.settings') }}" @if (request()->routeIs('admin.settings')) aria-current="page" @endif>General</a>
    <a href="{{ route('admin.settings.branding') }}" @if (request()->routeIs('admin.settings.branding')) aria-current="page" @endif>Branding</a>
    <a href="{{ route('admin.settings.codes') }}" @if (request()->routeIs('admin.settings.codes')) aria-current="page" @endif>Reference codes</a>
    <a href="{{ route('admin.settings.deposit') }}" @if (request()->routeIs('admin.settings.deposit')) aria-current="page" @endif>Deposit details</a>
    <a href="{{ route('admin.withdrawals') }}">Withdrawals</a>
    <a href="{{ route('admin.settings.system') }}" @if (request()->routeIs('admin.settings.system')) aria-current="page" @endif>System</a>
</nav>
