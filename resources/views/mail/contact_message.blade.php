<x-mail::message>
# New message from the Contact page

**From:** {{ $name }} ({{ $email }})
**About:** {{ $topic }}

<x-mail::panel>
{{ $body }}
</x-mail::panel>

Reply to this email to answer {{ $name }} directly.
</x-mail::message>
