@php
    $fmt = fn (int $c) => \App\Support\Money::format($c);
@endphp
<x-marketing-layout title="Questions">
    <section class="cc-pagehead" data-bg="amber">
        <div class="cc-wrap">
            <x-crumb>Questions</x-crumb>
            <h1 class="cc-title1">Questions</h1>
            <p class="cc-lead">Plain answers to what people ask us most. Select a question to read the answer.</p>
        </div>
    </section>

    <section class="cc-section" data-bg="gray">
        <div class="cc-wrap">
            <div class="cc-faq-split">
            <div class="cc-faq-photo"><div class="cc-photo tall" aria-hidden="true"><span>Photo</span></div></div>
            <div class="cc-faq">
                <details class="cc-q" data-reveal>
                    <summary>Who can join?</summary>
                    <p>Anyone aged {{ config('drinkwell.min_age') }} or older. You confirm your date of birth when you sign up.</p>
                </details>
                <details class="cc-q" data-reveal>
                    <summary>How much do I put in?</summary>
                    <p>Half the cost of the machine. That is {{ $fmt($tiers['commercial']['stake']) }} for the {{ $tiers['commercial']['label'] }} and {{ $fmt($tiers['enterprise']['stake']) }} for the {{ $tiers['enterprise']['label'] }}. Your co-partner puts in the other half.</p>
                </details>
                <details class="cc-q" data-reveal>
                    <summary>How do I get a co-partner?</summary>
                    <p>After you sign up we find you a co-partner, usually within 1 business day. Your status page says "Finding a partner" until then. When you are paired, we email you their name and payment details and show them on your dashboard.</p>
                </details>
                <details class="cc-q" data-reveal>
                    <summary>How does the machine earn money?</summary>
                    <p>Every drink sells at {{ $fmt(config('drinkwell.unit_price_cents')) }}. The {{ $tiers['commercial']['label'] }} holds {{ $tiers['commercial']['capacity'] }} drinks and the {{ $tiers['enterprise']['label'] }} holds {{ $tiers['enterprise']['capacity'] }}, and each machine sells out every day.</p>
                </details>
                <details class="cc-q" data-reveal>
                    <summary>How much will I earn each day?</summary>
                    <p>About {{ $fmt($tiers['commercial']['share']) }} a day on the {{ $tiers['commercial']['label'] }} and about {{ $fmt($tiers['enterprise']['share']) }} on the {{ $tiers['enterprise']['label'] }}. The daily fee moves a little, so your payout stays inside a small range: {{ $fmt($tiers['commercial']['band_min']) }} to {{ $fmt($tiers['commercial']['band_max']) }} and {{ $fmt($tiers['enterprise']['band_min']) }} to {{ $fmt($tiers['enterprise']['band_max']) }}.</p>
                </details>
                <details class="cc-q" data-reveal>
                    <summary>What is the bottling and logistics fee?</summary>
                    <p>It is taken from the day's sales before anything is split. It pays for filling, restocking and delivery. What is left, the pool, is divided equally between you and your co-partner.</p>
                </details>
                <details class="cc-q" data-reveal>
                    <summary>When do payouts start?</summary>
                    <p>The day your co-partner confirms your deposit. Until then your dashboard says you are waiting for them to confirm.</p>
                </details>
                <details class="cc-q" data-reveal>
                    <summary>How do I pay my deposit?</summary>
                    <p>You send it straight to your co-partner, because you are buying your half of a machine they already own. It never goes to us. The account details and reference are in your pairing email and on your dashboard. There is no card payment on this site.</p>
                </details>
                <details class="cc-q" data-reveal>
                    <summary>Can I see my history?</summary>
                    <p>Yes. Your dashboard lists every payout, and you can download your ledger as a spreadsheet.</p>
                </details>
                <details class="cc-q" data-reveal>
                    <summary>How is my information protected?</summary>
                    <p>The bank details you enter are stored encrypted, and only the last four digits are ever shown back to you. The Privacy page has the full list of what we keep.</p>
                </details>
                <details class="cc-q" data-reveal>
                    <summary>Who do I ask if I am stuck?</summary>
                    <p>Write to {{ $brand->supportEmail }} and tell us what you were doing. A real person reads every message.</p>
                </details>
            </div>
            </div>
            <div class="cc-btn-row"><a class="af-btn af-btn-black" href="{{ route('register') }}">Get started</a><a class="af-btn cc-btn-outline" href="{{ route('earnings') }}">See your earnings</a></div>
        </div>
    </section>
    <x-related :links="[['program', 'How it works'], ['contact', 'Contact us']]" />
</x-marketing-layout>
