@php
    $sections = [
        ['What is collected', 'Name, email address, phone number, date of birth and city that you type in, and a record of what you do on the site.'],
        ['Bank details', 'The bank numbers you enter (a routing number in the United States, or transit and institution numbers in Canada) and your account number are stored encrypted and only the last four digits are shown. No money is ever moved from the account.'],
        ['Age check', 'We confirm you are old enough to join from your date of birth. No document or photo is collected.'],
        ['Email', 'We send you a one-time code when you sign in for the first time, reset your password or take money out, and a message with your pairing details.'],
        ['Keeping and deleting', 'Your details are kept for as long as you are a member. Ask us at any time to close your account and remove them.'],
    ];
@endphp
<x-marketing-layout title="Privacy">
    <section class="cc-pagehead" data-bg="gray">
        <div class="cc-wrap">
            <x-crumb>Privacy</x-crumb><h1 class="cc-title1">Privacy</h1></div>
    </section>
    <section class="cc-section" data-bg="gray">
        <div class="cc-wrap cc-prosewrap">
            <div class="cc-prose">
                @foreach ($sections as $i => [$title, $text])
                    <h2 id="priv-{{ $i + 1 }}">{{ $i + 1 }}. {{ $title }}</h2>
                    <p>{{ $text }}</p>
                @endforeach
            </div>
        </div>
    </section>
</x-marketing-layout>
