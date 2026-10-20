<x-admin-layout title="Audit log">
    <div class="pf-page">
        <form method="GET" action="{{ route('admin.audit') }}" class="pf-filter">
            <x-field name="admin" label="Administrator" type="select">
                <option value="0">Everyone</option>
                @foreach ($admins as $a)
                    <option value="{{ $a->id }}" @selected($admin_id === $a->id)>{{ $a->name }}</option>
                @endforeach
            </x-field>
            <x-field name="action" label="Action" type="select">
                <option value="">Any action</option>
                @foreach ($actions as $a)
                    <option value="{{ $a }}" @selected($action === $a)>{{ $a }}</option>
                @endforeach
            </x-field>
            <button type="submit" class="pf-btn">Filter</button>
        </form>

        <section class="pf-panel" aria-label="Audit log">
            @if ($rows->isEmpty())
                <p class="pf-text">No actions recorded{{ $admin_id || $action !== '' ? ' for this filter' : ' yet' }}.</p>
            @else
                <div class="pf-tablewrap">
                    <table class="pf-table">
                        <caption class="sr-only">Administrator actions, newest first</caption>
                        <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Subject</th><th>IP</th></tr></thead>
                        <tbody>
                            @foreach ($rows as $r)
                                <tr>
                                    <td data-label="When" class="pf-mono">{{ $r->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td data-label="Who">{{ $r->admin?->name ?? 'Unknown' }}</td>
                                    <td data-label="Action" class="pf-mono">{{ $r->action }}</td>
                                    <td data-label="Subject" class="pf-mono">{{ $r->subject_type ? $r->subject_type.($r->subject_id ? ' #'.$r->subject_id : '') : '-' }}</td>
                                    <td data-label="IP" class="pf-mono">{{ $r->ip }}</td>
                                </tr>
                                @if ($r->before !== null || $r->after !== null)
                                    <tr class="pf-row-detail">
                                        <td colspan="5">
                                            <details>
                                                <summary class="pf-link">Before and after</summary>
                                                <div class="pf-ba">
                                                    <div><p class="pf-label">Before</p><pre class="pf-mono pf-pre">{{ json_encode($r->before, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre></div>
                                                    <div><p class="pf-label">After</p><pre class="pf-mono pf-pre">{{ json_encode($r->after, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre></div>
                                                </div>
                                            </details>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="pf-pager"><x-pagination :paginator="$rows" /></div>
            @endif
        </section>
        <p class="pf-note">Append-only. Nothing here can be edited or deleted.</p>
    </div>
</x-admin-layout>
