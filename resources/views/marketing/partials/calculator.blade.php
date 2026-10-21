@php $fmt = fn (int $c) => \App\Support\Money::format($c); @endphp
<div class="cc-calc">
    <div class="cc-calc-panel">
        <fieldset class="cc-calc-tiers">
            <legend>1. Pick a machine</legend>
            @foreach ($tiers as $key => $t)
                <label class="cc-calc-tier">
                    <input type="radio" name="calc-tier" value="{{ $key }}" x-model="tier">
                    <span class="cc-calc-tier-name">{{ $t['label'] }}</span>
                    <span class="cc-calc-tier-sub">{{ $fmt($t['stake']) }} deposit</span>
                </label>
            @endforeach
        </fieldset>

        <p class="cc-calc-legend" id="calc-days-label">2. Choose the number of days</p>
        <div class="cc-calc-days">
            <button type="button" class="cc-calc-step" aria-label="One day less" x-on:click="days = Math.max(1, days - 1)">-</button>
            <output aria-labelledby="calc-days-label"><b x-text="days"></b> <span>days</span></output>
            <button type="button" class="cc-calc-step" aria-label="One day more" x-on:click="days = Math.min(60, days + 1)">+</button>
        </div>
        <input id="calc-days" class="cc-calc-range" type="range" min="1" max="60" step="1" x-model.number="days" aria-labelledby="calc-days-label">
        <div class="cc-calc-scale"><span>1</span><span>30</span><span>60</span></div>
    </div>

    <div class="cc-calc-panel">
        <div class="cc-calc-tiles">
            <div class="cc-calc-tile is-coral"><small>Your share a day</small><b x-text="money(t.share)"></b></div>
            <div class="cc-calc-tile is-mint"><small>Earned in <span x-text="days"></span> days</small><b x-text="money(total)"></b></div>
            <div class="cc-calc-tile is-amber"><small>Your deposit</small><b x-text="money(t.stake)"></b></div>
            <div class="cc-calc-tile is-cream"><small>Left after deposit</small><b x-text="money(net)"></b></div>
        </div>
        <p class="cc-calc-back">Your deposit is back in about <b x-text="paybackExact"></b> days.</p>
        <p class="cc-calc-legend">Total paid to you, day by day</p>
        <div class="cc-calc-chart"><canvas x-ref="curve" role="img" aria-label="Total payout day by day, against the deposit"></canvas></div>
    </div>
</div>
<p class="cc-calc-note">Figures use the middle of the daily band. Real days vary by a small amount and are shown on your dashboard.</p>
