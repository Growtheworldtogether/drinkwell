@php $fmt = fn (int $c) => \App\Support\Money::format($c); @endphp
<x-admin-layout :title="'Machine '.$machine->serial_no">
    <div class="pf-page">
        @if (session('status'))
            <p class="pf-notice" role="status">{{ session('status') }}</p>
        @endif
        <x-stat-strip>
            <x-stat label="Stock" :value="$snap['stock'].' / '.$snap['capacity']" />
            <x-stat label="State" :value="$snap['state_label']" />
            <x-stat label="Clock" :value="$snap['date'].' '.$snap['clock']" />
            <x-stat label="Revenue today" :cents="$snap['revenue_today_cents']" />
        </x-stat-strip>

        <div class="mt-4"><x-simulation-toolbar :machine="$machine" /></div>

        <section class="pf-panel mt-4" aria-labelledby="mc-owners">
            <div class="pf-panel-head"><p class="pf-label" id="mc-owners">Owners</p></div>
            <div class="pf-tablewrap">
                <table class="pf-table">
                    <caption class="sr-only">Owners</caption>
                    <thead><tr><th>Slot</th><th>Name</th><th>Email</th><th>Deposit</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($owners as $o)
                            <tr>
                                <td data-label="Slot" class="pf-mono">{{ $o->slot_no }}</td>
                                <td data-label="Name">{{ $o->user->name }} @if ($o->user->profile?->is_simulated)<span class="pf-note">simulated</span>@endif</td>
                                <td data-label="Email" class="pf-mono">{{ $o->user->email }}</td>
                                <td data-label="Deposit" class="pf-mono">{{ $fmt($o->capital_cents) }}</td>
                                <td data-label="Status">{{ $o->settlement_status->label() }}</td>
                                <td data-label="">
                                    @if ($o->settlement_status === \App\Enums\SettlementStatus::PendingSettlement)
                                        <form method="POST" action="{{ route('admin.ownerships.confirm', $o) }}">@csrf<button type="submit" class="pf-btn">Confirm deposit</button></form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="pf-panel mt-4" aria-labelledby="mc-days">
            <div class="pf-panel-head"><p class="pf-label" id="mc-days">Settled days</p></div>
            @if ($days->isEmpty())
                <x-empty-state title="No settled days">Days settle at midnight or when you fast-forward.</x-empty-state>
            @else
                <div class="pf-tablewrap">
                    <table class="pf-table">
                        <caption class="sr-only">Settled days</caption>
                        <thead><tr><th>Date</th><th>Units</th><th>Gross</th><th>Fee</th><th>Pool</th></tr></thead>
                        <tbody>
                            @foreach ($days as $d)
                                <tr>
                                    <td data-label="Date" class="pf-mono">{{ $d->operating_date->toDateString() }}</td>
                                    <td data-label="Units" class="pf-mono">{{ $d->units_sold }}</td>
                                    <td data-label="Gross" class="pf-mono">{{ $fmt($d->gross_cents) }}</td>
                                    <td data-label="Fee" class="pf-mono">{{ $fmt($d->fee_cents) }}</td>
                                    <td data-label="Pool" class="pf-mono">{{ $fmt($d->pool_cents) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-admin-layout>
