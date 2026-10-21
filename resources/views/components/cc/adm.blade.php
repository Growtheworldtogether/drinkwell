@props(['title', 'heading' => null, 'bg' => 'gray', 'width' => null])
{{-- One administrator page: the shared glass header, the page title, the saved-message notice, then the content. --}}
<x-app-layout :title="$title" mode="admin">
    <section class="cc-screen cc-adm" data-bg="{{ $bg }}" style="padding-top:120px">
        <div class="cc-wrap" @if ($width) style="max-width:{{ $width }}" @endif>
            @if (session('status'))
                <x-cc.notice>{{ session('status') }}</x-cc.notice>
            @endif
            @if (session('sim_error'))
                <x-cc.notice type="error">{{ session('sim_error') }}</x-cc.notice>
            @endif
            <h1 class="cc-title1" style="font-size:clamp(40px,6vw,88px);margin-bottom:28px">{{ $heading ?? $title }}</h1>
            {{ $slot }}
        </div>
    </section>
</x-app-layout>
