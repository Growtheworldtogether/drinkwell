<x-marketing-layout title="Locations">
    <section class="cc-pagehead" data-bg="mint">
        <div class="cc-wrap">
            <h1 class="cc-title1">Where your machine stands</h1>
            <p class="cc-lead">{{ $total }} machines in {{ $cities }} cities, placed where people wait: hospitals, train stations and airports.</p>
        </div>
    </section>

    <section class="cc-section" data-bg="gray">
        <div class="cc-wrap">
            <div class="cc-map" data-reveal>
                <div data-map data-public-map data-src="{{ route('public.machines') }}" role="img" aria-label="Map of the places that host a machine"></div>
            </div>
            <div class="cc-loc-groups">
                @foreach ($groups as $type => $rows)
                    <section class="cc-loc" data-reveal aria-labelledby="loc-{{ $loop->index }}">
                        <h2 id="loc-{{ $loop->index }}" class="cc-loc-title">{{ \Illuminate\Support\Str::plural(\Illuminate\Support\Str::title(str_replace('_', ' ', $type))) }} <span>{{ $rows->count() }}</span></h2>
                        <ul>
                            @foreach ($rows as $m)
                                <li><strong>{{ $m->facility_name }}</strong><span>{{ $m->city }}</span></li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        </div>
    </section>
</x-marketing-layout>
