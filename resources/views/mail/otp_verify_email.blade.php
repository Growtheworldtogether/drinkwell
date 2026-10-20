<x-mail::message>
# Verify your email

Enter this code on the verification page to confirm your email address.

<x-mail::panel>
{{ $code }}
</x-mail::panel>

The code expires in {{ $minutes }} minutes and works once. If you did not create an account, ignore this email.
</x-mail::message>
