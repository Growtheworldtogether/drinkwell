<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Mail\PasswordChanged;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/** State changes on an account that come from the auth screens (SPEC 5). */
class AccountService
{
    public function __construct(private MailDispatcher $mail) {}

    /** Public registration always creates an applicant, whatever the request says. */
    public function registerApplicant(string $name, string $email, string $password): User
    {
        $user = User::create([
            'name' => $name,
            'email' => OtpService::normalize($email),
            'password' => $password,
            'role' => Role::Applicant,
            'status' => UserStatus::Active,
        ]);

        event(new Registered($user));

        return $user;
    }

    public function markEmailVerified(User $user): void
    {
        if ($user->email_verified_at !== null) {
            return;
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        event(new Verified($user));
    }

    /**
     * Set a new password, sign the account out everywhere else, and email a notice.
     *
     * $reset is true for the code flow, where nobody is signed in on this device.
     */
    public function changePassword(User $user, string $password, bool $reset = false): void
    {
        $user->forceFill([
            'password' => $password,
            'remember_token' => Str::random(60),
        ])->save();

        $guard = Auth::guard('web');
        $previous = $guard->user();

        $guard->setUser($user);
        $guard->logoutOtherDevices($password);

        if ($previous === null) {
            $guard->forgetUser();
        }

        if ($reset) {
            event(new PasswordReset($user));
        }

        $this->mail->sendQuietly(
            new PasswordChanged($user->name, now()->format('M j, Y H:i').' UTC'),
            $user,
        );
    }
}
