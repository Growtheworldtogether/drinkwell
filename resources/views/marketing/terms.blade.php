@php
    $sections = [
        ['Demo', 'This site is a demo. Do not send real money because of anything on it.'],
        ['Eligibility', 'Members must be '.config('drinkwell.min_age').' or older and live in the United States or Canada. Joining needs a reference code from us.'],
        ['Your deposit', 'You pay your deposit directly to your co-partner, never to us. We confirm it before your share of the payouts starts.'],
        ['Your half belongs to you', 'Your share of the machine is fixed at one half. It cannot be reduced, moved to another person or changed without your written agreement.'],
        ['No hidden fees', 'The only charge is the bottling and logistics fee taken from each day\'s sales, shown line by line in your ledger. There is no sign-up fee, no monthly fee and no fee to take money out.'],
        ['Change of mind', 'You can cancel at no cost at any time before your deposit is confirmed. If you change your mind within 14 days after it is confirmed, we arrange for your half to be bought back for your full deposit, less any payouts already paid to you.'],
        ['Selling your half', 'You can ask to sell your half at any time. We help find a buyer, and there is no fee for leaving.'],
        ['Machine trouble', 'Repairs, damage, theft and replacement of the machine are paid for by the platform, never by you. If your machine is out of service for more than 3 days in a row, we move your share to a working machine of the same size, at no cost to you.'],
        ['Getting paid on time', 'Each day\'s share is added to your balance when the day closes. Withdrawals of '.\App\Support\Money::format(config('drinkwell.withdrawal_min_cents')).' or more are reviewed within '.config('drinkwell.withdrawal_review_days').' business days. If a review takes longer, we tell you why by email.'],
        ['Your records', 'Every sale, fee, payout and withdrawal is kept in your ledger. You can download it at any time, and it is kept for 7 years.'],
        ['Currency', 'Amounts are shown in US dollars. Members with a Canadian bank account are paid the US dollar amount, and their bank converts it at its own rate.'],
        ['If something happens to you', 'If you pass away, your half and your balance pass to your estate, like any other thing you own.'],
        ['Questions and complaints', 'Write to us and a person replies within 1 business day. If you are not happy with the answer, ask for it to be looked at again by someone who was not involved.'],
        ['Your account', 'Keep your password private. An administrator may suspend an account used for abuse, and tells the owner why.'],
        ['Changes', 'We give you at least 30 days\' notice by email before these terms change. If you do not agree with a change, you can leave under the Selling your half section, with no fee. Version '.config('drinkwell.terms_version').'.'],
    ];
@endphp
<x-marketing-layout title="Terms">
    <section class="cc-pagehead" data-bg="gray">
        <div class="cc-wrap">
            <x-crumb>Terms</x-crumb><h1 class="cc-title1">Terms of use</h1></div>
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
