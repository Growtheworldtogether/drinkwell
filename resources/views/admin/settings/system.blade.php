@php
    $u = $updates;
    $when = fn (?string $iso) => $iso ? \Illuminate\Support\Carbon::parse($iso)->timezone(config('app.timezone'))->format('M j, Y g:i a') : '';
    $nice = fn (?string $tag) => $tag && preg_match('/^release-(\d{4})(\d{2})(\d{2})-(\d{2})(\d{2})$/', $tag, $m)
        ? \Illuminate\Support\Carbon::create((int) $m[1], (int) $m[2], (int) $m[3], (int) $m[4], (int) $m[5], 0, 'UTC')->timezone(config('app.timezone'))->format('M j, Y g:i a')
        : ($tag ?: 'Unknown');
@endphp
<x-cc.adm title="Settings" heading="System">
    @include('admin.settings._tabs')

    @if (session('error'))
        <x-cc.notice type="error">{{ session('error') }}</x-cc.notice>
    @endif

    <div class="cc-panel" id="updates">
        <h2>Updates</h2>
        <dl class="cc-dl">
            <div class="cc-kv"><dt>This site</dt><dd>{{ $u['current'] ? 'Version of '.$nice($u['current']['tag']) : 'Not recorded yet' }}</dd></div>
            @if ($u['latest'])
                <div class="cc-kv"><dt>Newest on GitHub</dt><dd>Version of {{ $nice($u['latest']['tag']) }}</dd></div>
            @endif
            @if (! empty($u['last']['at']))
                <div class="cc-kv"><dt>Last {{ ($u['last']['action'] ?? 'update') === 'restore' ? 'restore' : 'update' }}</dt><dd>{{ $when($u['last']['at']) }}</dd></div>
            @endif
        </dl>
        @unless ($u['current'])
            <p class="cc-hint" style="margin:0">This site was set up before automatic updates. The first update records its version.</p>
        @endunless

        @if ($u['job'] && ($u['job']['phase'] ?? '') !== 'finish' && time() - (int) ($u['job']['saved_at'] ?? 0) > 60)
            <div class="dw-update-box" style="background:rgb(253, 241, 217)">
                <p style="margin:0"><strong>An update was interrupted</strong> before it finished. You can carry on where it stopped, or stop it and keep the site as it was before.</p>
                <div style="display:flex;flex-wrap:wrap;gap:12px">
                    <a class="af-btn af-btn-black" style="flex:1 1 220px" href="{{ route('admin.settings.system.progress') }}">Continue the update</a>
                    <form method="POST" action="{{ route('admin.settings.system.cancel') }}" style="flex:1 1 220px;display:flex">@csrf<button type="submit" class="af-btn cc-btn-outline" style="width:100%">Stop it and undo</button></form>
                </div>
            </div>
        @elseif ($u['newer'])
            <div class="dw-update-box">
                <p style="margin:0"><strong>A new version is ready.</strong> Installing takes a minute or two. Members see a short "back in a minute" page meanwhile. Your settings, uploaded files and database are kept, and the files it replaces are backed up first.</p>
                <form method="POST" action="{{ route('admin.settings.system.install') }}" class="cc-form" x-data="{ busy: false }" x-on:submit="busy = true">
                    @csrf
                    <x-cc.field name="developer_code" id="up-code" label="Developer code" maxlength="40" autocomplete="off" autocapitalize="characters" spellcheck="false" required :error="$errors->update->first('developer_code')" />
                    <button type="submit" class="af-btn af-btn-black" x-bind:disabled="busy"><span x-text="busy ? 'Installing, please keep this page open...' : 'Install update'">Install update</span></button>
                </form>
            </div>
        @elseif ($u['latest'])
            <p style="margin:0"><span class="cc-tone ok">You have the latest version</span></p>
        @endif

        <p class="cc-hint" style="margin:0">Updates come from the Releases page of github.com/{{ $u['repo'] }}. To make a new one, open Actions there, choose Build release zip, then Run workflow.</p>
        <form method="POST" action="{{ route('admin.settings.system.check') }}">
            @csrf
            <button type="submit" class="af-btn cc-btn-outline" style="width:auto">Check for updates</button>
        </form>

        @if ($u['backup'] && ! $u['backup']['restored_at'])
            <details class="dw-restore">
                <summary>Something wrong after the last update? Restore the previous version</summary>
                <p class="cc-hint">This puts back the files from before the update on {{ $when($u['backup']['created_at']) }}. Members' accounts, money and records are not changed.</p>
                <form method="POST" action="{{ route('admin.settings.system.restore') }}" class="cc-form">
                    @csrf
                    <x-cc.field name="developer_code" id="restore-code" label="Developer code" maxlength="40" autocomplete="off" autocapitalize="characters" spellcheck="false" required :error="$errors->restore->first('developer_code')" />
                    <button type="submit" class="af-btn cc-btn-outline">Restore previous version</button>
                </form>
            </details>
        @endif
    </div>

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
        <h2>Database and caches</h2>
        <p class="cc-hint">Updates do this for you. Use it only if you changed files by hand in File Manager: it applies any database changes and clears the old caches. Members' money and records are kept.</p>
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

    <style>
        #updates { display: grid; gap: 16px; }
        #updates > * { min-width: 0; }
        #updates .cc-hint, #updates p { overflow-wrap: anywhere; }
        #updates .cc-dl { margin: 0; }
        .dw-update-box { display: grid; gap: 16px; padding: 20px; border: 2px solid black; border-radius: 22px; background: rgb(233, 247, 241); }
        .dw-restore summary { cursor: pointer; font-weight: 700; text-decoration: underline; text-underline-offset: 4px; }
        .dw-restore[open] { display: grid; gap: 12px; }
    </style>
</x-cc.adm>
