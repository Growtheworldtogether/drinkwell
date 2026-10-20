@php $fmt = fn (int $c) => \App\Support\Money::format($c); @endphp
<x-marketing-layout title="How it works">
    <section class="af-band">
        <div class="af-wrap">
            <h1 class="af-h1">How it works</h1>
            <p class="af-lead">One machine, two co-partners, one price: {{ $fmt(config('drinkwell.unit_price_cents')) }} a drink.</p>

            <h2 class="mt-8 text-h3">The daily split</h2>
            <p class="mt-2 text-body">Gross sales are capacity times the drink price. A bottling and logistics fee comes off, and the pool is split equally between the two co-partners. The fee changes a little from day to day, so your payout stays inside a band. Money is counted in whole cents and the pool is always even, so nobody is short a cent.</p>
            <div class="pf-tablewrap mt-4">
                <table class="pf-table">
                    <caption class="sr-only">Daily figures by option</caption>
                    <thead><tr><th>Option</th><th>Capacity</th><th>Gross</th><th>Fee</th><th>Pool</th><th>Your share</th><th>Band</th></tr></thead>
                    <tbody>
                        @foreach ($tiers as $t)
                            <tr>
                                <td data-label="Option">{{ $t['label'] }}</td>
                                <td data-label="Capacity" class="pf-mono">{{ $t['capacity'] }}</td>
                                <td data-label="Gross" class="pf-mono">{{ $fmt($t['gross']) }}</td>
                                <td data-label="Fee" class="pf-mono">{{ $fmt($t['fee']) }}</td>
                                <td data-label="Pool" class="pf-mono">{{ $fmt($t['pool']) }}</td>
                                <td data-label="Your share" class="pf-mono">{{ $fmt($t['share']) }}</td>
                                <td data-label="Band" class="pf-mono">{{ $fmt($t['band_min']) }} to {{ $fmt($t['band_max']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <h2 class="mt-8 text-h3">A day in the life of a machine</h2>
            <p class="mt-2 text-body">Sales start at 06:00 and follow a daily curve with a morning commuter rush and a lunch peak. Every unit is sold by 23:59, and a driver restocks overnight.</p>

            <h2 class="mt-8 text-h3">Paying the deposit</h2>
            <p class="mt-2 text-body">There is no card form on this site. After pairing, you get an email with your machine, your co-partner's contact details, a reference and a ready-made message to send. Your share stays "pending settlement" until the deposit is confirmed.</p>

            <p class="dw-demo mt-8">This is a classroom simulation. People and amounts are fictional, and no real money moves.</p>
        </div>
    </section>
</x-marketing-layout>
