<x-marketing-layout title="Search">
    <section class="cc-pagehead" data-bg="amber">
        <div class="cc-wrap">
            <x-crumb>Search</x-crumb>
            <h1 class="cc-title1">Search</h1>
            <p class="cc-lead">Type a word or a question, like "deposit", "withdraw" or "who can join".</p>
        </div>
    </section>

    <section class="cc-section" data-bg="gray">
        <div class="cc-wrap">
            <div class="cc-help cc-search-box">
                <form action="{{ route('search') }}" method="get" role="search">
                    <label class="sr-only" for="search-q">Search the site</label>
                    <input id="search-q" name="q" type="search" value="{{ $q }}" placeholder="Start typing here..." maxlength="100" @if ($q === '') autofocus @endif>
                    <button class="af-btn af-btn-black" type="submit">Search</button>
                </form>
            </div>

            @if ($q !== '')
                <p class="cc-search-count" role="status">
                    @if ($results === [])
                        No results for "{{ $q }}".
                    @else
                        {{ count($results) }} {{ count($results) === 1 ? 'result' : 'results' }} for "{{ $q }}"
                    @endif
                </p>
            @endif

            @if ($results !== [])
                <ol class="cc-results">
                    @foreach ($results as $r)
                        <li>
                            <a href="{{ $r['url'] }}" class="cc-result">
                                <span class="cc-result-page">{{ $r['page'] }}</span>
                                <span class="cc-result-title">{{ $r['heading'] }}</span>
                                <span class="cc-result-text">{!! $r['snippet'] !!}</span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            @elseif ($q !== '')
                <div class="cc-search-empty">
                    <p>Try a shorter word, or start from one of these.</p>
                    <div class="cc-chips">
                        <a href="{{ route('questions') }}">Questions</a>
                        <a href="{{ route('program') }}">How it works</a>
                        <a href="{{ route('earnings') }}">Your earnings</a>
                        <a href="{{ route('contact') }}">Contact us</a>
                    </div>
                </div>
            @else
                <div class="cc-search-empty">
                    <p>People often look for</p>
                    <div class="cc-chips">
                        @foreach (['deposit', 'withdraw', 'who can join', 'daily payout', 'co-partner', 'fee'] as $word)
                            <a href="{{ route('search', ['q' => $word]) }}">{{ ucfirst($word) }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
</x-marketing-layout>
