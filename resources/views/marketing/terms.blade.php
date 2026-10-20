@php
    $sections = [
        ['What this is', 'This site is a classroom project. It shows how a shared vending machine platform could work. It is not an offer to buy or sell anything, and no real money moves.'],
        ['Eligibility', 'The simulation asks for a date of birth and only lets in people aged '.config('drinkwell.min_age').' or older, to match the story it tells.'],
        ['Deposits and payouts', 'Deposits and payouts shown here are made-up figures. Settlement instructions are an example of an off-platform process. Never send real money because of anything on this site.'],
        ['Simulated people', 'Your co-partner is set up by the platform. Their name and account details are fictional.'],
        ['Your account', 'Keep your password private. An administrator may suspend an account used for abuse.'],
        ['Changes', 'These terms may change as the project changes. Version '.config('drinkwell.terms_version').'.'],
    ];
@endphp
<x-marketing-layout title="Terms">
    <section class="cc-pagehead" data-bg="gray">
        <div class="cc-wrap"><h1 class="cc-title1">Terms of use</h1></div>
    </section>
    <section class="cc-section" data-bg="gray">
        <div class="cc-wrap cc-prosewrap">
            <div class="cc-prose">
                @foreach ($sections as $i => [$title, $text])
                    <h2 id="terms-{{ $i + 1 }}">{{ $i + 1 }}. {{ $title }}</h2>
                    <p>{{ $text }}</p>
                @endforeach
            </div>
        </div>
    </section>
</x-marketing-layout>
