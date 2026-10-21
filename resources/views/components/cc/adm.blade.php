@props(['title', 'heading' => null, 'eyebrow' => 'Administrator', 'lead' => null, 'bg' => 'gray'])
{{-- One administrator page: sidebar shell, page head, saved-message notices, then the content. --}}
<x-app-layout :title="$title" mode="admin" :bg="$bg">
    <div class="cc-adm">
        @if (session('status'))
            <x-cc.notice>{{ session('status') }}</x-cc.notice>
        @endif
        @if (session('sim_error'))
            <x-cc.notice type="error">{{ session('sim_error') }}</x-cc.notice>
        @endif
        <x-cc.head :title="$heading ?? $title" :eyebrow="$eyebrow" :lead="$lead">{{ $actions ?? '' }}</x-cc.head>
        {{ $slot }}
    </div>
</x-app-layout>
