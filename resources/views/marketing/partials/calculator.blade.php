@php $fmt = fn (int $c) => \App\Support\Money::format($c); @endphp
            <div class="cc-options">
                <div class="cc-option">
                    <fieldset class="min-w-0">
                        <legend class="mb-3 text-label uppercase tracking-label text-af-500">Option</legend>
                        <div class="flex flex-col gap-3">
                            @foreach ($tiers as $key => $t)
                                <label class="cc-pick">
                                    <input type="radio" name="calc-tier" value="{{ $key }}" x-model="tier">
                                    <span class="block font-medium">{{ $t['label'] }} <span class="font-mono tabular-nums">({{ $fmt($t['stake']) }} deposit)</span></span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <p class="mt-6 text-label uppercase tracking-label text-af-500" id="calc-days-label">Days</p>
                    <div class="cc-stepper">
                        <button type="button" aria-label="One day less" @click="days = Math.max(1, days - 1)">-</button>
                        <output aria-labelledby="calc-days-label" x-text="days"></output>
                        <button type="button" aria-label="One day more" @click="days = Math.min(60, days + 1)">+</button>
                    </div>
                    <input id="calc-days" class="dw-range" type="range" min="1" max="60" step="1" x-model.number="days" aria-labelledby="calc-days-label">
                    <dl class="cc-facts">
                        <div><dt>Share a day</dt><dd x-text="money(t.share)"></dd></div>
                        <div><dt>Earned in <span x-text="days"></span> days</dt><dd x-text="money(total)"></dd></div>
                        <div><dt>Deposit</dt><dd x-text="money(t.stake)"></dd></div>
                        <div><dt>Left after deposit</dt><dd x-text="money(net)"></dd></div>
                        <div><dt>Deposit back in</dt><dd><span x-text="paybackExact"></span> days (day <span x-text="payback"></span>)</dd></div>
                    </dl>
                </div>
                <div class="cc-option">
                    <p class="text-label uppercase tracking-label text-af-500">Amortization (cumulative payout against deposit)</p>
                    <div class="dw-curve mt-3"><canvas x-ref="curve" role="img" aria-label="Cumulative payout against the deposit"></canvas></div>
                </div>
            </div>
            <p class="mt-6 text-af-700">Figures use the middle of the daily band. Real days vary by a small amount and are shown on your dashboard.</p>
