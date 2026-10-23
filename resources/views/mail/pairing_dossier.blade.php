<x-mail::message>
# {{ $updated ? 'Updated payment details' : 'Your machine and co-partner' }}

Hello {{ $userName }},

@if ($updated)
We corrected the payment details for your deposit. Please use the details below.
@else
You are paired with a co-partner on one machine.
@endif

<x-mail::panel>
**Machine {{ $machine->serial_no }}**
{{ $machine->facility_name }}, {{ $machine->city }}
{{ $machine->tier->label() }}. Your deposit: {{ $stake }}.
</x-mail::panel>

**Your co-partner**
{{ $partner->name }}@if ($partner->profile?->city), {{ $partner->profile->city }}@endif

**Send your deposit to your co-partner.** You are buying half of a machine they already own, so the deposit goes straight to them and never to {{ $brand->name }}.

@if ($request)
<x-mail::panel>
**Account holder:** {{ $request->account_holder }}
**Bank:** {{ $request->bank_name }}
@if ($request->routing_number)
**Routing number:** {{ $request->routing_number }}
@endif
**Account number:** {{ $request->account_number }}
**Amount:** {{ $stake }}
**Reference:** {{ $reference }}
@if ($request->note)

{{ $request->note }}
@endif
</x-mail::panel>
@endif

When you have sent it, open your status page and choose "I sent my deposit". Your share starts the day your co-partner confirms it.

<x-mail::button :url="$statusUrl">See your status</x-mail::button>
</x-mail::message>
