<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head')
</head>
<body class="bg-af-white text-af-ink font-sans antialiased">
    @php
        $role = auth()->user()?->role;
        $groups = [
            $role === \App\Enums\Role::Partner
                ? [['hub.dashboard', 'Dashboard'], ['onboard.status', 'Pairing and deposit'], ['profile.edit', 'Profile']]
                : [['onboard', 'Onboarding'], ['profile.edit', 'Profile']],
        ];
    @endphp
    <x-shell :groups="$groups" :title="$title" :actions="$actions ?? null">
        {{ $slot }}
    </x-shell>
    @livewireScriptConfig
</body>
</html>
