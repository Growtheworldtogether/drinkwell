<x-mail::message>
# Your password was changed

Hello {{ $userName }},

The password for your account was changed on {{ $changedAt }}. Sessions on other devices were signed out.

If you made this change, no action is needed. If you did not, reset your password now and contact {{ $brand->supportEmail }}.
</x-mail::message>
