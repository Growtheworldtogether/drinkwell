@php $fmt = fn (int $c) => \App\Support\Money::format($c); @endphp
            <div class="cc-options">
                @foreach ($tiers as $key => $t)
                    <article data-reveal class="cc-option" aria-labelledby="tier-{{ $key }}">
                        <div class="cc-photo cc-option-photo" aria-hidden="true"><img src="{{ asset('images/home/'.($key === 'enterprise' ? 'machine-large' : 'machine-small').'.jpg') }}" alt="" loading="lazy" decoding="async"></div>
                        <h3 id="tier-{{ $key }}">{{ $t['label'] }}</h3>
                        <p class="cc-big">{{ $fmt($t['share']) }}</p>
                        <p class="text-af-700">your share a day, deposit back in about {{ $t['payback'] }} days</p>
                        <dl class="cc-facts">
                            <div><dt>Machine cost</dt><dd>{{ $fmt($t['machine_cost']) }}</dd></div>
                            <div><dt>Your 50% deposit</dt><dd>{{ $fmt($t['stake']) }}</dd></div>
                            <div><dt>Capacity (sells out daily)</dt><dd>{{ $t['capacity'] }} drinks</dd></div>
                            <div><dt>Gross a day</dt><dd>{{ $fmt($t['gross']) }}</dd></div>
                            <div><dt>Bottling and logistics fee</dt><dd>{{ $fmt($t['fee']) }}</dd></div>
                            <div><dt>Pool to split</dt><dd>{{ $fmt($t['pool']) }}</dd></div>
                            <div><dt>Daily band</dt><dd>{{ $fmt($t['band_min']) }} to {{ $fmt($t['band_max']) }}</dd></div>
                        </dl>
                    </article>
                @endforeach
            </div>
