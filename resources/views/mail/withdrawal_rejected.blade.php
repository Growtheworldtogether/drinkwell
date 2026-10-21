<x-mail::message>
# Your withdrawal was declined

We could not approve the withdrawal of {{ $amount }} (request {{ $reference }}).

<x-mail::panel>
{{ $reason }}
</x-mail::panel>

The amount is back in your balance. You can ask again whenever you like.
</x-mail::message>
