@php
    $sections = [
        ['What is collected', 'Name, email address, phone number, date of birth and city that you type in, and a record of what you do on the site.'],
        ['Bank details', 'The routing and account numbers you enter are stored encrypted and only the last four digits are shown. No money is ever moved from the account.'],
        ['Identity check', 'The identity check is simulated. No document or photo is collected.'],
        ['Email', 'The platform sends a one-time code, and a message with your pairing details. Messages addressed to simulated co-partners are only written to a log and are never sent.'],
        ['Keeping and deleting', 'Your data stays in this project\'s database. Ask the administrator to remove your account when the course ends.'],
    ];
@endphp
<x-marketing-layout title="Privacy">
    <section class="cc-pagehead" data-bg="gray">
        <div class="cc-wrap"><h1 class="cc-title1">Privacy</h1></div>
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
