@php $fmt = fn (int $c) => \App\Support\Money::format($c); $first = \Illuminate\Support\Str::before(auth()->user()->name, ' '); @endphp
<x-app-layout title="Dashboard" bg="gray">
    <div data-hub data-src="{{ route('hub.telemetry') }}">
        @if (session('status'))
            <x-cc.notice>{{ session('status') }}</x-cc.notice>
        @endif

        <x-cc.head :title="'Hello, '.$first" :eyebrow="'Machine '.$snap['serial']" :lead="$snap['facility'].', '.$snap['city']">
            <a class="af-btn af-btn-black" href="{{ route('hub.withdraw') }}">Withdraw</a>
            <a class="af-btn cc-btn-outline" href="{{ route('hub.ledger.csv') }}">Download CSV</a>
        </x-cc.head>

        @if ($snap['settlement'] === 'pending_settlement')
            <x-cc.notice>Waiting for your co-partner to confirm your deposit, so no payouts accrue yet.</x-cc.notice>
            @if ($deposit)
                @include('onboard._deposit', $deposit)
            @endif
        @endif

        <div class="cc-grid is-hero">
            <div class="cc-herocard">
                <div>
                    <small>Available to withdraw</small>
                    <b>{{ $fmt($available) }}</b>
                    <p>Your payouts, ready whenever you are. We review each request within {{ config('drinkwell.withdrawal_review_days') }} business days.</p>
                </div>
                <a class="af-btn af-btn-black" href="{{ route('hub.withdraw') }}">Withdraw money</a>
            </div>
            <div class="cc-kpis">
                <div class="cc-kpi is-mint"><small>Paid to you</small><b data-bind="paid">{{ $fmt($snap['paid_cents']) }}</b><span>all payouts so far</span></div>
                <div class="cc-kpi is-amber"><small>Accruing today</small><b data-bind="accrued">{{ $fmt($snap['accrued_today_cents']) }}</b><span>estimate until the day closes</span></div>
                <div class="cc-kpi"><small>Deposit returned</small><b data-bind="payback">{{ $snap['payback_pct'] }}%</b><div class="cc-gauge" role="img" aria-label="Deposit returned"><i data-payback-bar style="width: {{ $snap['payback_pct'] }}%"></i></div></div>
                <div class="cc-kpi"><small>Machine clock</small><b data-bind="clock">{{ $snap['clock'] }}</b><span data-bind="date">{{ $snap['date'] }}</span></div>
            </div>
        </div>

        <div class="cc-grid is-two">
            <div class="cc-panel">
                <h2>Live inventory</h2>
                <p>Drinks left: <strong><span data-bind="stock">{{ $snap['stock'] }}</span> of <span data-bind="capacity">{{ $snap['capacity'] }}</span></strong> (<span data-bind="fill">{{ $snap['fill_pct'] }}%</span>)</p>
                <div class="cc-gauge" role="img" aria-label="Stock level"><i data-gauge-fill data-state="{{ $snap['state'] }}" style="width: {{ $snap['fill_pct'] }}%"></i></div>
                <dl class="cc-dl" style="margin-top:16px">
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
        </div>

        <div class="cc-grid is-two">
            <div class="cc-panel">
                <h2>Your machine</h2>
                <div class="dw-map" data-facility-map data-lat="{{ $snap['lat'] }}" data-lng="{{ $snap['lng'] }}" data-name="{{ $snap['facility'] }}" role="img" aria-label="Map of the facility"></div>
                <p style="margin-top:12px"><strong>{{ $snap['facility'] }}</strong>, {{ $snap['city'] }} ({{ $snap['serial'] }})</p>
                @if ($partnership)
                    <dl class="cc-dl" style="margin-top:16px">
                        <div class="cc-kv"><dt>Your co-partner</dt><dd>{{ $partnership['partner']->name }}{{ $partnership['partner']->profile?->city ? ', '.$partnership['partner']->profile->city : '' }}</dd></div>
                        <div class="cc-kv"><dt>Your deposit</dt><dd>{{ $fmt($partnership['deposit']) }}</dd></div>
                        @if ($partnership['confirmed'])
                            <div class="cc-kv"><dt>Confirmed</dt><dd>{{ $partnership['confirmed']->format('M j, Y') }}</dd></div>
                        @endif
                    </dl>
                    <p style="margin-top:12px"><a class="cc-link" href="{{ route('onboard.status') }}">See deposit details</a></p>
                @endif
            </div>

            <div class="cc-panel">
                <h2>Daily payouts</h2>
                <div class="dw-chart"><canvas data-payout-chart data-rows='@json($chart)' role="img" aria-label="Daily payouts"></canvas></div>
                <h2 style="margin-top:24px;font-size:20px">Machine log</h2>
                <ul class="cc-ticker" data-logs>
                    @foreach ($snap['logs'] as $l)
                        <li class="dw-tick"><span>{{ $l['at'] }}  {{ $l['event'] }}</span><span>{{ $l['message'] }}</span></li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="cc-panel">
            <div class="cc-panel-head"><h2>Payout ledger</h2><a class="cc-more" href="{{ route('hub.ledger.csv') }}">Download CSV</a></div>
            @if ($ledger->isEmpty())
                <p>No payouts yet. Payouts appear here after the first day closes with a confirmed deposit.</p>
            @else
                <div class="cc-tablewrap">
                    <table class="cc-tbl is-stack">
                        <caption class="sr-only">Daily payouts</caption>
                        <thead><tr><th>Date</th><th>Drinks</th><th>Sales</th><th>Fee</th><th>Pool</th><th>Your payout</th><th>Reference</th></tr></thead>
                        <tbody>
                            @foreach ($ledger as $d)
                                <tr>
                                    <td data-label="Date">{{ $d->distribution_date->format('M j') }}</td>
                                    <td data-label="Drinks">{{ $d->day->units_sold }}</td>
                                    <td data-label="Sales">{{ $fmt($d->day->gross_cents) }}</td>
                                    <td data-label="Fee">{{ $fmt($d->day->fee_cents) }}</td>
                                    <td data-label="Pool">{{ $fmt($d->day->pool_cents) }}</td>
                                    <td data-label="Your payout"><strong>{{ $fmt($d->payout_cents) }}</strong></td>
                                    <td data-label="Reference">{{ $d->ach_ref }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
