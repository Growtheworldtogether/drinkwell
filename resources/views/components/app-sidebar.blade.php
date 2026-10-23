@props(['mode' => 'member'])
@php
    $user = auth()->user();
    $isPartner = $user?->role === \App\Enums\Role::Partner;
    $count = fn ($n) => $n > 0 ? $n : null;

    if ($mode === 'admin') {
        $groups = [
            'Operate' => [
                ['admin.overview', 'Overview', 'admin.overview', null],
                ['admin.pairings', 'Pairings', 'admin.pairings*', $count(\App\Models\PairingRequest::query()->where('status', 'finding')->count())],
                ['admin.withdrawals', 'Withdrawals', 'admin.withdrawals*', $count(\App\Models\Withdrawal::query()->where('status', 'pending')->count())],
                ['admin.machines', 'Machines', 'admin.machines*', null],
            ],
            'Platform' => [
                ['admin.settings', 'Settings', 'admin.settings*', null],
                ['admin.audit', 'Audit log', 'admin.audit', null],
                ['admin.emails', 'Email log', 'admin.emails*', null],
                ['admin.integrity', 'Integrity', 'admin.integrity*', null],
            ],
        ];
    } else {
        $mine = array_values(array_filter([
            $isPartner ? ['hub.dashboard', 'Dashboard', 'hub.dashboard', null] : null,
            $isPartner ? ['hub.withdraw', 'Withdraw', 'hub.withdraw*', null] : null,
            $isPartner ? null : ['onboard.status', 'Your progress', 'onboard.status', null],
            ['profile.edit', 'Profile', 'profile.*', null],
        ]));
        $groups = ['Your account' => $mine, 'Help' => [['contact', 'Contact us', 'contact', null], ['questions', 'Questions', 'questions', null]]];
    }
    $initials = collect(preg_split('/\s+/', trim((string) $user?->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp
<aside class="cc-side" id="app-side" x-bind:class="{ 'is-open': nav }" aria-label="Menu">
    <div class="cc-side-top">
        <a href="{{ $user?->homeUrl() ?? url('/') }}" class="af-brand"><x-brand.logo variant="light" /></a>
        <button type="button" class="cc-icon-btn cc-side-close" x-on:click="nav = false" aria-label="Close menu"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
    </div>

    <nav class="cc-side-nav" aria-label="{{ $mode === 'admin' ? 'Administrator' : 'Member' }}">
        @foreach ($groups as $label => $items)
            <p class="cc-side-label">{{ $label }}</p>
            @foreach ($items as [$route, $text, $pattern, $badge])
                <a href="{{ route($route) }}" class="cc-side-link" @if (request()->routeIs($pattern)) aria-current="page" @endif>
                    <span>{{ $text }}</span>
                    @if ($badge)<b class="cc-badge" aria-label="{{ $badge }} waiting">{{ $badge }}</b>@endif
                </a>
            @endforeach
        @endforeach
        @if ($mode === 'admin')
            <p class="cc-side-label">Site</p>
            <a href="{{ route('home') }}" class="cc-side-link"><span>View the public site</span></a>
        @endif
        <button type="button" class="cc-side-link cc-side-size" data-text-size aria-pressed="false"><span data-text-size-label>Bigger text</span><b aria-hidden="true">Aa</b></button>
    </nav>

    <div class="cc-side-user">
        <span class="cc-avatar" aria-hidden="true">{{ $initials ?: '?' }}</span>
        <div>
            <strong>{{ $user?->name }}</strong>
            <small>{{ $mode === 'admin' ? 'Administrator' : ($isPartner ? 'Co-partner' : 'Applicant') }}</small>
        </div>
    </div>
    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="af-btn cc-btn-outline cc-side-out">Sign out</button></form>
</aside>
