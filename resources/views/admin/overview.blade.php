@php $fmt = fn (int $c) => \App\Support\Money::format($c); @endphp
<x-cc.adm title="Overview">
    @if ($mailer === 'log')
        <x-cc.notice type="error"><strong>Email:</strong> the mailer is set to "log", so nothing is delivered to anyone.</x-cc.notice>
    @endif

    <div class="cc-tiles cc-tiles-6">
        <div class="cc-tile"><small>Machines</small><b>{{ $machines }}</b><span>{{ $open }} open</span></div>
        <a class="cc-tile" href="{{ route('admin.pairings') }}"><small>Waiting for a partner</small><b>{{ $waiting }}</b><span>open Pairings</span></a>
        <a class="cc-tile" href="{{ route('admin.withdrawals') }}"><small>Withdrawals waiting</small><b>{{ $withdrawals }}</b><span>open Withdrawals</span></a>
        <div class="cc-tile"><small>Co-partners</small><b>{{ $partners }}</b><span>signed up and paired</span></div>
        <div class="cc-tile"><small>Paid out</small><b>{{ $fmt($paid_cents) }}</b><span>all payouts so far</span></div>
        <div class="cc-tile"><small>Demo emails</small><b>{{ $simulated_mail }}</b><span>logged, never sent</span></div>
    </div>

    <div class="cc-panel">
        <h2>Deposits waiting for confirmation</h2>
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

    <p class="cc-hint">
        Integrity check:
        @if ($last_check)
            {{ $last_check['failed'] === [] ? 'passed' : 'failed ('.implode(', ', $last_check['failed']).')' }}, {{ \Illuminate\Support\Carbon::createFromTimestamp($last_check['at'])->diffForHumans() }}.
        @else
            not run yet.
        @endif
        <a class="cc-link" href="{{ route('admin.integrity') }}">Open</a>
    </p>
</x-cc.adm>
