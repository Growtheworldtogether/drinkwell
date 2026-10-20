@php $fmt = fn (int $c) => \App\Support\Money::format($c); $first = \Illuminate\Support\Str::before(auth()->user()->name, ' '); @endphp
<x-app-layout title="Dashboard">
    <section class="cc-screen" data-bg="gray" style="padding-top:120px">
        <div class="cc-wrap" data-hub data-src="{{ route('hub.telemetry') }}">
            @if (session('status'))
                <x-cc.notice>{{ session('status') }}</x-cc.notice>
            @endif
            <h1 class="cc-title1" style="font-size:clamp(40px,6vw,88px);margin-bottom:28px">Hello, {{ $first }}</h1>

            @if ($snap['settlement'] === 'pending_settlement')
                <x-cc.notice>Waiting for your co-partner to confirm your deposit, so no payouts accrue yet.</x-cc.notice>
                @if ($deposit)
                    @include('onboard._deposit', $deposit)
                @endif
            @endif

            <div class="cc-tiles">
                <div class="cc-tile"><small>Paid to you</small><b data-bind="paid">{{ $fmt($snap['paid_cents']) }}</b><span>all payouts so far</span></div>
                <div class="cc-tile"><small>Accruing today</small><b data-bind="accrued">{{ $fmt($snap['accrued_today_cents']) }}</b><span>estimate until the day closes</span></div>
                <div class="cc-tile"><small>Deposit returned</small><b data-bind="payback">{{ $snap['payback_pct'] }}%</b><span>of your deposit</span></div>
                <div class="cc-tile"><small>Machine clock</small><b data-bind="clock">{{ $snap['clock'] }}</b><span data-bind="date">{{ $snap['date'] }}</span></div>
            </div>

            <div class="cc-dgrid">
                <div class="cc-panel">
                    <h2>Live inventory</h2>
                    <p>Drinks left: <strong><span data-bind="stock">{{ $snap['stock'] }}</span> of <span data-bind="capacity">{{ $snap['capacity'] }}</span></strong> (<span data-bind="fill">{{ $snap['fill_pct'] }}%</span>)</p>
                    <div class="cc-gauge" role="img" aria-label="Stock level"><i data-gauge-fill data-state="{{ $snap['state'] }}" style="width: {{ $snap['fill_pct'] }}%"></i></div>
                    <dl class="cc-dl" style="margin-top:12px">
                        <div class="cc-kv"><dt>Status</dt><dd data-bind="state">{{ $snap['state_label'] }}</dd></div>
                        <div class="cc-kv"><dt>Sold today</dt><dd data-bind="sold">{{ $snap['sold_today'] }}</dd></div>
                        <div class="cc-kv"><dt>Revenue today</dt><dd data-bind="revenue">{{ $fmt($snap['revenue_today_cents']) }}</dd></div>
                        <div class="cc-kv"><dt>Temperature</dt><dd><span data-bind="temp">{{ number_format($snap['temp_c'], 1) }}</span> C</dd></div>
                    </dl>
                </div>

                <div class="cc-panel">
                    <h2>Sales ticker</h2>
                    <p class="cc-hint" style="margin-top:-4px">{{ $fmt(config('drinkwell.unit_price_cents')) }} a drink</p>
                    <ul class="cc-ticker" data-ticker>
                        @forelse ($snap['ticker'] as $t)
                            <li class="dw-tick"><span>{{ $t['time'] }}  unit {{ $t['unit'] }}</span><span>{{ $fmt($t['cents']) }}</span></li>
                        @empty
                            <li class="dw-tick"><span>No sales yet today</span><span></span></li>
                        @endforelse
                    </ul>
                </div>

                <div class="cc-panel">
                    <h2>Your machine</h2>
                    <div class="dw-map" data-facility-map data-lat="{{ $snap['lat'] }}" data-lng="{{ $snap['lng'] }}" data-name="{{ $snap['facility'] }}" role="img" aria-label="Map of the facility"></div>
                    <p style="margin-top:12px"><strong>{{ $snap['facility'] }}</strong>, {{ $snap['city'] }} ({{ $snap['serial'] }})</p>
                </div>

                <div class="cc-panel">
                    <h2>Daily payouts</h2>
                    <div class="dw-chart"><canvas data-payout-chart data-rows='@json($chart)' role="img" aria-label="Daily payouts"></canvas></div>
                    <h2 style="margin-top:20px;font-size:20px">Machine log</h2>
                    <ul class="cc-ticker" data-logs>
                        @foreach ($snap['logs'] as $l)
                            <li class="dw-tick"><span>{{ $l['at'] }}  {{ $l['event'] }}</span><span>{{ $l['message'] }}</span></li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="cc-panel" style="margin-top:16px">
                <h2>Payout ledger <a class="cc-back" href="{{ route('hub.ledger.csv') }}" style="float:right;margin:0;font-size:17px">Download CSV</a></h2>
                @if ($ledger->isEmpty())
                    <p>No payouts yet. Payouts appear here after the first day closes with a confirmed deposit.</p>
                @else
                    <div class="cc-tablewrap">
                        <table class="cc-tbl">
                            <caption class="sr-only">Daily payouts</caption>
                            <thead><tr><th>Date</th><th>Drinks</th><th>Sales</th><th>Fee</th><th>Pool</th><th>Your payout</th><th>Reference</th></tr></thead>
                            <tbody>
                                @foreach ($ledger as $d)
                                    <tr>
                                        <td>{{ $d->distribution_date->format('M j') }}</td>
                                        <td>{{ $d->day->units_sold }}</td>
                                        <td>{{ $fmt($d->day->gross_cents) }}</td>
                                        <td>{{ $fmt($d->day->fee_cents) }}</td>
                                        <td>{{ $fmt($d->day->pool_cents) }}</td>
                                        <td><strong>{{ $fmt($d->payout_cents) }}</strong></td>
                                        <td>{{ $d->ach_ref }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-app-layout>
