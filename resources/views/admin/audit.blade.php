<x-cc.adm title="Audit log">
    <div class="cc-panel">
        <form method="GET" action="{{ route('admin.audit') }}" class="cc-demo-form">
            <x-cc.field name="admin" label="Administrator" type="select">
                <option value="0">Everyone</option>
                @foreach ($admins as $a)
                    <option value="{{ $a->id }}" @selected($admin_id === $a->id)>{{ $a->name }}</option>
                @endforeach
            </x-cc.field>
            <x-cc.field name="action" label="Action" type="select">
                <option value="">Any action</option>
                @foreach ($actions as $a)
                    <option value="{{ $a }}" @selected($action === $a)>{{ $a }}</option>
                @endforeach
            </x-cc.field>
            <button type="submit" class="af-btn af-btn-black">Filter</button>
        </form>
    </div>

    <div class="cc-panel">
        @if ($rows->isEmpty())
            <p>No actions recorded{{ $admin_id || $action !== '' ? ' for this filter' : ' yet' }}.</p>
        @else
            @foreach ($rows as $r)
                <details>
                    <summary>
                        <strong>{{ $r->action }}</strong>&nbsp;· {{ $r->admin?->name ?? 'Unknown' }} · {{ $r->created_at->format('M j, g:i a') }}
                    </summary>
                    <dl class="cc-dl">
                        <div class="cc-kv"><dt>When</dt><dd>{{ $r->created_at->format('Y-m-d H:i:s') }}</dd></div>
                        <div class="cc-kv"><dt>Subject</dt><dd>{{ $r->subject_type ? $r->subject_type.($r->subject_id ? ' #'.$r->subject_id : '') : 'None' }}</dd></div>
                        <div class="cc-kv"><dt>IP address</dt><dd>{{ $r->ip }}</dd></div>
                    </dl>
                    @if ($r->before !== null || $r->after !== null)
                        <div class="cc-two">
                            <div><p class="cc-hint">Before</p><pre class="cc-pre">{{ json_encode($r->before, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre></div>
                            <div><p class="cc-hint">After</p><pre class="cc-pre">{{ json_encode($r->after, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre></div>
                        </div>
                    @endif
                </details>
            @endforeach
            <x-pagination :paginator="$rows" />
        @endif
    </div>
    <p class="cc-hint">Append-only. Nothing here can be edited or deleted.</p>
</x-cc.adm>
