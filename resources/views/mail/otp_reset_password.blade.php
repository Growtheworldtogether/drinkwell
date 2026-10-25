<x-mail::message>
# Reset your password

Enter this code on the reset page, together with your new password.

<x-mail::panel code>
{{ $code }}
</x-mail::panel>

The code expires in {{ $minutes }} minutes and works once. If you did not ask to reset your password, ignore this email. Your password has not changed.
</x-mail::message>
