<x-mail::message>
# You were paired with a new co-partner

Hello {{ $partnerName }},

{{ $userName }} ({{ $userEmail }}) is now your co-partner on machine {{ $machine->serial_no }} at {{ $machine->facility_name }}, {{ $machine->city }}.

Escrow reference: **{{ $reference }}**

This message is part of a classroom simulation and was not sent to anyone.
</x-mail::message>
