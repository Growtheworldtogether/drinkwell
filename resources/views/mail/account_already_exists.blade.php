<x-mail::message>
# You already have an account

Hello {{ $userName }},

Someone tried to register with this email address, but it already has an account. If it was you, sign in at {{ $loginUrl }}. If you forgot the password, you can reset it at {{ $resetUrl }}.

If it was not you, no action is needed. Nobody can get into your account without your password.
</x-mail::message>
