<x-mail::message>
# Your machine and co-partner

Hello {{ $userName }},

You are paired with a co-partner on one machine. **This is a classroom simulation: the people and amounts are fictional, and no real money moves.**

<x-mail::panel>
**Machine {{ $machine->serial_no }}**
{{ $machine->facility_name }}, {{ $machine->city }}
{{ $machine->tier->label() }}. Your deposit: {{ $stake }}.
</x-mail::panel>

**Your co-partner (simulated)**
{{ $partner->name }}
{{ $partner->email }}
{{ $partner->profile?->phone }}

**Settlement happens off the platform.** {{ $brand->name }} takes no card or online payment. In the story, each partner sends their deposit by bank wire to the escrow account, quoting this reference:

**{{ $reference }}**

Your ownership stays "pending settlement" until the deposit is confirmed. Payouts start the day after.

**A message you can send to your co-partner**

<x-mail::panel>
{!! nl2br(e($copy)) !!}
</x-mail::panel>

<x-mail::button :url="$statusUrl">See your status</x-mail::button>
</x-mail::message>
