<x-cc.adm title="Settings" heading="System">
    @include('admin.settings._tabs')

    @if (session('error'))
        <x-cc.notice type="error">{{ session('error') }}</x-cc.notice>
    @endif

    <div class="cc-panel">
        <h2>Site health</h2>
        <dl class="cc-dl">
            @foreach ($checks as $c)
                <div class="cc-kv">
                    <dt>{{ $c['label'] }}@if ($c['ok'] !== true)<br><span class="cc-hint" style="font-weight:400">{{ $c['note'] }}</span>@endif</dt>
                    <dd><span class="cc-tone {{ $c['ok'] === true ? 'ok' : '' }}">{{ $c['ok'] === true ? 'Good' : ($c['ok'] === false ? 'Fix now' : 'Check') }}</span></dd>
                </div>
            @endforeach
        </dl>
    </div>

    <div class="cc-panel">
        <h2>Install updates</h2>
        <p class="cc-hint">After you upload a new version of the files, press this once. It applies any database changes and clears the old caches. Members' money and records are kept.</p>
        @if ($pending !== [])
            <p><strong>{{ count($pending) }} database {{ count($pending) === 1 ? 'change is' : 'changes are' }} waiting.</strong></p>
        @else
            <p>The database is up to date.</p>
        @endif
        <form method="POST" action="{{ route('admin.settings.system.update') }}">
            @csrf
            <button type="submit" class="af-btn af-btn-black" style="width:auto">Install updates</button>
        </form>
    </div>
</x-cc.adm>
