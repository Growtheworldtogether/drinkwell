<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('layouts.partials.head')
</head>
<body class="bg-af-white text-af-ink font-sans antialiased">
    @php
        $groups = [
            'Operate' => [['admin.overview', 'Overview'], ['admin.pairings', 'Pairings'], ['admin.machines', 'Machines']],
            'Platform' => [['admin.settings', 'Settings']],
            'Records' => [['admin.audit', 'Audit log'], ['admin.emails', 'Email log'], ['admin.integrity', 'Integrity']],
        ];
    @endphp
    <x-shell :groups="$groups" :title="$title" fluid>
        {{ $slot }}
    </x-shell>
    @livewireScriptConfig
</body>
</html>
