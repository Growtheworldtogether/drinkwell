@php $fmt = fn (int $c) => \App\Support\Money::format($c); @endphp
<x-admin-layout title="Overview">
    <div class="pf-page">
        @if (session('status'))
            <p class="pf-notice" role="status">{{ session('status') }}</p>
        @endif
        @if ($mailer === 'log')
            <p class="pf-notice" role="alert"><b>Email</b> The mailer is set to log, so nothing is delivered.</p>
        @endif

        <x-stat-strip>
            <x-stat label="Machines" :value="$machines" :note="$open.' open'" />
            <x-stat label="Waiting for a partner" :value="$waiting" />
            <x-stat label="Withdrawals waiting" :value="$withdrawals" />
            <x-stat label="Co-partners" :value="$partners" />
            <x-stat label="Paid out" :cents="$paid_cents" />
            <x-stat label="Simulated emails" :value="$simulated_mail" note="logged, never sent" />
        </x-stat-strip>

        <section class="pf-panel mt-4" aria-labelledby="ov-pending">
            <div class="pf-panel-head"><p class="pf-label" id="ov-pending">Deposits waiting for confirmation</p></div>
            @if ($pending->isEmpty())
                <x-empty-state title="Nothing waiting">New co-partners appear here until their deposit is confirmed.</x-empty-state>
            @else
                <div class="pf-tablewrap">
                    <table class="pf-table">
                        <caption class="sr-only">Pending deposits</caption>
                        <thead><tr><th>Partner</th><th>Machine</th><th>Deposit</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($pending as $o)
                                <tr>
                                    <td data-label="Partner">{{ $o->user->name }}</td>
                                    <td data-label="Machine" class="pf-mono"><a class="pf-link" href="{{ route('admin.machines.show', $o->machine_id) }}">{{ $o->machine->serial_no }}</a></td>
                                    <td data-label="Deposit" class="pf-mono">{{ $fmt($o->capital_cents) }}</td>
                                    <td data-label=""><form method="POST" action="{{ route('admin.ownerships.confirm', $o) }}">@csrf<button type="submit" class="pf-btn">Confirm deposit</button></form></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <p class="pf-note mt-4">
            Integrity check:
            @if ($last_check)
                {{ $last_check['failed'] === [] ? 'passed' : 'failed ('.implode(', ', $last_check['failed']).')' }}, {{ \Illuminate\Support\Carbon::createFromTimestamp($last_check['at'])->diffForHumans() }}.
            @else
                not run yet.
            @endif
            <a class="pf-link" href="{{ route('admin.integrity') }}">Open</a>
        </p>
    </div>
</x-admin-layout>
