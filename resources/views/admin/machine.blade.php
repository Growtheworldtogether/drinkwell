@php $fmt = fn (int $c) => \App\Support\Money::format($c); @endphp
<x-cc.adm :title="'Machine '.$machine->serial_no" :heading="$machine->serial_no" :lead="$machine->facility_name.', '.$machine->city">
    <div class="cc-kpis cc-kpis-4" style="margin-bottom:16px">
        <div class="cc-kpi is-coral"><small>Stock</small><b>{{ $snap['stock'] }} / {{ $snap['capacity'] }}</b><span>drinks</span></div>
        <div class="cc-kpi is-mint"><small>State</small><b>{{ $snap['state_label'] }}</b><span>{{ $machine->facility_name }}</span></div>
        <div class="cc-kpi is-amber"><small>Clock</small><b>{{ $snap['clock'] }}</b><span>{{ $snap['date'] }}</span></div>
        <div class="cc-kpi"><small>Revenue today</small><b>{{ $fmt($snap['revenue_today_cents']) }}</b><span>so far</span></div>
    </div>

    <div class="cc-panel">
        <h2>Owners</h2>
        <div class="cc-tablewrap">
            <table class="cc-tbl is-stack">
                <caption class="sr-only">Owners</caption>
                <thead><tr><th>Slot</th><th>Name</th><th>Email</th><th>Deposit</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($owners as $o)
                        <tr>
                            <td data-label="Slot">{{ $o->slot_no }}</td>
                            <td data-label="Name">{{ $o->user->name }} @if ($o->user->profile?->is_simulated)<span class="cc-hint">demo partner</span>@endif</td>
                            <td data-label="Email">{{ $o->user->email }}</td>
                            <td data-label="Deposit">{{ $fmt($o->capital_cents) }}</td>
                            <td data-label="Status">{{ $o->settlement_status->label() }}</td>
                            <td data-label="">
                                @if ($o->settlement_status === \App\Enums\SettlementStatus::PendingSettlement)
                                    <form method="POST" action="{{ route('admin.ownerships.confirm', $o) }}">@csrf<button type="submit" class="af-btn af-btn-black">Confirm deposit</button></form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="cc-panel">
        <h2>Settled days</h2>
        @if ($days->isEmpty())
            <p>No settled days yet. Days settle at midnight or when you fast-forward.</p>
        @else
            <div class="cc-tablewrap">
                <table class="cc-tbl is-stack">
                    <caption class="sr-only">Settled days</caption>
                    <thead><tr><th>Date</th><th>Units</th><th>Gross</th><th>Fee</th><th>Pool</th></tr></thead>
                    <tbody>
                        @foreach ($days as $d)
                            <tr>
                                <td data-label="Date">{{ $d->operating_date->toDateString() }}</td>
                                <td data-label="Units">{{ $d->units_sold }}</td>
                                <td data-label="Gross">{{ $fmt($d->gross_cents) }}</td>
                                <td data-label="Fee">{{ $fmt($d->fee_cents) }}</td>
                                <td data-label="Pool">{{ $fmt($d->pool_cents) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <x-simulation-toolbar :machine="$machine" />
</x-cc.adm>
