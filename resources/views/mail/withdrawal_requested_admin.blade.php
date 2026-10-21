<x-mail::message>
# A withdrawal needs approval

{{ $name }} asked to withdraw {{ $amount }} (reference {{ $reference }}).

<x-mail::button :url="$url">Open withdrawals</x-mail::button>
</x-mail::message>
