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
    <section class="af-band">
        <div class="af-wrap af-legal">
            <h1 class="af-h1">Privacy</h1>
            @foreach ($sections as $i => [$title, $text])
                <section class="mt-8" aria-labelledby="priv-{{ $i + 1 }}">
                    <h2 id="priv-{{ $i + 1 }}" class="text-h3"><span class="font-mono tabular-nums">{{ $i + 1 }}.</span> {{ $title }}</h2>
                    <p class="mt-2 text-body">{{ $text }}</p>
                </section>
            @endforeach
        </div>
    </section>
</x-marketing-layout>
