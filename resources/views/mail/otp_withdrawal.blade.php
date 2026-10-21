<x-mail::message>
# Confirm your withdrawal

Enter this code to confirm taking out {{ $amount }}.

<x-mail::panel>
{{ $code }}
</x-mail::panel>

The code expires in {{ $minutes }} minutes and works once. If you did not ask for this withdrawal, ignore this email and change your password. Nothing is taken out until you enter the code.
</x-mail::message>
