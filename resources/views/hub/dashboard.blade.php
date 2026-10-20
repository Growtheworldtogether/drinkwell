@php $fmt = fn (int $c) => \App\Support\Money::format($c); @endphp
<x-app-layout title="Dashboard">
    <div class="pf-page" data-hub data-src="{{ route('hub.telemetry') }}">
        @if (session('status'))
            <p class="pf-notice" role="status">{{ session('status') }}</p>
        @endif
        @if ($snap['settlement'] === 'pending_settlement')
            <p class="pf-notice" role="status">Your deposit is pending settlement, so no payouts accrue yet. <a class="pf-link" href="{{ route('onboard.status') }}">See the steps</a>.</p>
        @endif

        <x-stat-strip>
            <x-stat label="Paid to you" :cents="$snap['paid_cents']" />
            <x-stat label="Accruing today" :cents="$snap['accrued_today_cents']" note="estimate until the day closes" />
            <x-stat label="Deposit returned"><span data-bind="payback">{{ $snap['payback_pct'] }}%</span></x-stat>
            <x-stat label="Machine clock"><span data-bind="clock">{{ $snap['clock'] }}</span></x-stat>
        </x-stat-strip>
        <div class="dw-bar mt-2" role="img" aria-label="Deposit returned"><span data-payback-bar style="width: {{ $snap['payback_pct'] }}%"></span></div>

        <div class="dw-grid2 mt-4">
            <x-dark-band variant="panel">
                <p class="dw-tele-label">Live inventory, <span data-bind="date">{{ $snap['date'] }}</span></p>
                <div class="dw-gauge-wrap mt-3">
                    <div class="dw-gauge" role="img" aria-label="Stock level"><div class="dw-gauge-fill" data-gauge-fill data-state="{{ $snap['state'] }}" style="height: {{ $snap['fill_pct'] }}%"></div></div>
                    <div>
                        <p class="dw-tele-num"><span data-bind="stock">{{ $snap['stock'] }}</span> / <span data-bind="capacity">{{ $snap['capacity'] }}</span></p>
                        <p class="dw-tele-label">units left (<span data-bind="fill">{{ $snap['fill_pct'] }}%</span>)</p>
                        <p class="dw-tele-num mt-3" data-bind="state">{{ $snap['state_label'] }}</p>
                        <p class="dw-tele-label">temperature <span data-bind="temp">{{ number_format($snap['temp_c'], 1) }}</span> C</p>
                    </div>
                </div>
                <dl class="dw-tele-label mt-4">
                    <div>Sold today <span class="dw-tele-num" data-bind="sold">{{ $snap['sold_today'] }}</span></div>
                    <div class="mt-2">Revenue today <span class="dw-tele-num" data-bind="revenue">{{ $fmt($snap['revenue_today_cents']) }}</span></div>
                </dl>
            </x-dark-band>

            <x-dark-band variant="panel">
                <p class="dw-tele-label">Sales ticker ({{ $fmt(config('drinkwell.unit_price_cents')) }} a drink)</p>
                <ul class="dw-list mt-2" data-ticker>
                    @forelse ($snap['ticker'] as $t)
                        <li class="dw-tick"><span>{{ $t['time'] }}  unit {{ $t['unit'] }}</span><span>{{ $fmt($t['cents']) }}</span></li>
                    @empty
                        <li class="dw-tick"><span>No sales yet today</span><span></span></li>
                    @endforelse
                </ul>
                <p class="dw-tele-label mt-4">Machine log</p>
                <ul class="dw-list mt-2" data-logs>
                    @foreach ($snap['logs'] as $l)
                        <li class="dw-tick"><span>{{ $l['at'] }}  {{ $l['event'] }}</span><span>{{ $l['message'] }}</span></li>
                    @endforeach
                </ul>
            </x-dark-band>
        </div>

        <div class="dw-grid2 mt-4">
            <x-panel title="Facility">
                <p class="mb-3 text-body"><b>{{ $snap['facility'] }}</b>, {{ $snap['city'] }} <span class="pf-mono">({{ $snap['serial'] }})</span></p>
                <div class="dw-map" data-facility-map data-lat="{{ $snap['lat'] }}" data-lng="{{ $snap['lng'] }}" data-name="{{ $snap['facility'] }}" role="img" aria-label="Map of the facility"></div>
            </x-panel>
            <x-panel title="Daily payouts">
                <div class="dw-chart"><canvas data-payout-chart data-rows='@json($chart)' role="img" aria-label="Daily payouts"></canvas></div>
            </x-panel>
        </div>

        <div class="mt-4"><x-simulation-toolbar :machine="$machine" /></div>

        <section class="pf-panel mt-4" aria-labelledby="hub-ledger">
            <div class="pf-panel-head">
                <p class="pf-label" id="hub-ledger">Payout ledger</p>
                <a class="pf-link" href="{{ route('hub.ledger.csv') }}">Export CSV</a>
            </div>
            @if ($ledger->isEmpty())
                <x-empty-state title="No payouts yet">Payouts appear here after the first day closes with a confirmed deposit. Use the toolbar to fast-forward a day.</x-empty-state>
            @else
                <div class="pf-tablewrap">
                    <table class="pf-table">
                        <caption class="sr-only">Daily payouts</caption>
                        <thead><tr><th>Date</th><th>Units</th><th>Gross</th><th>Fee</th><th>Pool</th><th>Your payout</th><th>Reference</th></tr></thead>
                        <tbody>
                            @foreach ($ledger as $d)
                                <tr>
                                    <td data-label="Date" class="pf-mono">{{ $d->distribution_date->toDateString() }}</td>
                                    <td data-label="Units" class="pf-mono">{{ $d->day->units_sold }}</td>
                                    <td data-label="Gross" class="pf-mono">{{ $fmt($d->day->gross_cents) }}</td>
                                    <td data-label="Fee" class="pf-mono">{{ $fmt($d->day->fee_cents) }}</td>
                                    <td data-label="Pool" class="pf-mono">{{ $fmt($d->day->pool_cents) }}</td>
                                    <td data-label="Your payout" class="pf-mono">{{ $fmt($d->payout_cents) }}</td>
                                    <td data-label="Reference" class="pf-mono">{{ $d->ach_ref }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
