<x-mail::message>
# Your withdrawal was approved

{{ $amount }} is on its way to {{ $bank }}, account ending {{ $last4 }}.

<x-mail::panel>
Request {{ $reference }}
Payment reference {{ $paidReference }}
</x-mail::panel>

Bank transfers usually arrive within 1 to 3 business days.
</x-mail::message>
