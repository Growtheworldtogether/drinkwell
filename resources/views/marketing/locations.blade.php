<x-marketing-layout title="Locations">
    <section class="cc-pagehead" data-bg="mint">
        <div class="cc-wrap">
            <x-crumb>Locations</x-crumb>
            <h1 class="cc-title1">Where your machine stands</h1>
            <p class="cc-lead">{{ $total }} machines in {{ $cities }} cities across the United States and Canada, placed where people wait: hospitals, train stations and airports.</p>
        </div>
    </section>

    <section class="cc-section cc-center" data-bg="cream">
        <div class="cc-wrap">
            <p class="cc-statement" data-reveal>People wait in hospitals, train stations and airports, and that is where your machine stands.</p>
        </div>
    </section>

    <section class="cc-section" data-bg="gray">
        <div class="cc-wrap">
            <div class="cc-map" data-reveal>
                <div data-map data-public-map data-src="{{ route('public.machines') }}" role="img" aria-label="Map of the places that host a machine"></div>
            </div>
        </div>
    </section>

    <section class="cc-section" data-bg="cream">
        <div class="cc-wrap">
            <h2 class="cc-h2 cc-center" data-reveal>Browse by place</h2>
            <div class="cc-loc-groups">
                @foreach ($groups as $type => $rows)
                    <section class="cc-loc cc-loc-{{ $loop->index % 4 }}" data-reveal aria-labelledby="loc-{{ $loop->index }}">
                        @php $photo = 'images/locations/'.\Illuminate\Support\Str::slug($type).'.jpg'; @endphp
                        <div class="cc-photo" aria-hidden="true">@if (is_file(public_path($photo)))<img src="{{ asset($photo) }}" alt="" loading="lazy" decoding="async">@else<span>Photo</span>@endif</div>
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
    <x-related :links="[['program', 'How it works'], ['earnings', 'Your earnings']]" />
</x-marketing-layout>
