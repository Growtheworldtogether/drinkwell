<x-mail::message>
# A partner reports a deposit

{{ $partnerName }} ({{ $partnerEmail }}) says the deposit of {{ $amount }} was sent. Confirm it in the console to start payouts.

<x-mail::button :url="$url">Open the machine</x-mail::button>
</x-mail::message>
