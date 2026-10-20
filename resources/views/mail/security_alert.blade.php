<x-mail::message>
# Admin sign-in locked

The sign-in form for the administrator account {{ $adminEmail }} was locked for {{ $minutes }} minutes after {{ $attempts }} failed attempts in a row.

If this was you, wait and try again. If it was not, the password may be under attack: reset it from the sign-in page once the lock ends and review the security log.
</x-mail::message>
