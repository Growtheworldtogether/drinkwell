@php $fmt = fn (int $c) => \App\Support\Money::format($c); @endphp
<x-cc.adm title="Overview" lead="What needs you today, and how the platform is doing.">
    @if ($mailer === 'log')
        <x-cc.notice type="error"><strong>Email:</strong> the mailer is set to "log", so nothing is delivered to anyone.</x-cc.notice>
    @endif

    <div class="cc-kpis cc-kpis-3" style="margin-bottom:16px">
        <a class="cc-kpi is-coral" href="{{ route('admin.pairings') }}"><small>Waiting for a partner</small><b>{{ $waiting }}</b><span>Open Pairings</span></a>
        <a class="cc-kpi is-amber" href="{{ route('admin.withdrawals') }}"><small>Withdrawals waiting</small><b>{{ $withdrawals }}</b><span>Open Withdrawals</span></a>
        <a class="cc-kpi is-mint" href="{{ route('admin.pairings') }}"><small>Deposits to confirm</small><b>{{ $pending->count() }}</b><span>Members say they paid</span></a>
    </div>

    <div class="cc-kpis cc-kpis-4" style="margin-bottom:16px">
        <div class="cc-kpi"><small>Machines</small><b>{{ $machines }}</b><span>{{ $open }} open</span></div>
        <div class="cc-kpi"><small>Co-partners</small><b>{{ $partners }}</b><span>signed up and paired</span></div>
        <div class="cc-kpi"><small>Paid out</small><b>{{ $fmt($paid_cents) }}</b><span>all payouts so far</span></div>
        <div class="cc-kpi"><small>Demo emails</small><b>{{ $simulated_mail }}</b><span>logged, never sent</span></div>
    </div>

    <div class="cc-grid is-split">
        <div class="cc-panel">
            <div class="cc-panel-head"><h2>Deposits waiting for confirmation</h2><a class="cc-more" href="{{ route('admin.pairings') }}">All pairings</a></div>
            @if ($pending->isEmpty())
                <p>Nothing waiting. New co-partners appear here until their deposit is confirmed.</p>
            @else
                <div class="cc-tablewrap">
                    <table class="cc-tbl is-stack">
                        <caption class="sr-only">Pending deposits</caption>
                        <thead><tr><th>Partner</th><th>Machine</th><th>Deposit</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($pending as $o)
                                <tr>
                                    <td data-label="Partner">{{ $o->user->name }}</td>
                                    <td data-label="Machine"><a class="cc-link" href="{{ route('admin.machines.show', $o->machine_id) }}">{{ $o->machine->serial_no }}</a></td>
                                    <td data-label="Deposit">{{ $fmt($o->capital_cents) }}</td>
                                    <td data-label=""><form method="POST" action="{{ route('admin.ownerships.confirm', $o) }}">@csrf<button type="submit" class="af-btn af-btn-black">Confirm deposit</button></form></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="cc-panel">
            <div class="cc-panel-head"><h2>Recent activity</h2><a class="cc-more" href="{{ route('admin.audit') }}">Audit log</a></div>
            @forelse ($recent as $r)
                @if ($loop->first)<ul class="cc-feed">@endif
                <li><strong>{{ $r->action }}</strong><small>{{ $r->admin?->name ?? 'Unknown' }} · {{ $r->created_at->diffForHumans() }}</small></li>
                @if ($loop->last)</ul>@endif
            @empty
                <p>Nothing yet. Actions you take in the console show up here.</p>
            @endforelse
            <p class="cc-hint" style="margin-top:12px">
                Integrity check:
                @if ($last_check)
                    {{ $last_check['failed'] === [] ? 'passed' : 'failed ('.implode(', ', $last_check['failed']).')' }}, {{ \Illuminate\Support\Carbon::createFromTimestamp($last_check['at'])->diffForHumans() }}.
                @else
                    not run yet.
                @endif
                <a class="cc-link" href="{{ route('admin.integrity') }}">Open</a>
            </p>
        </div>
    </div>
</x-cc.adm>
