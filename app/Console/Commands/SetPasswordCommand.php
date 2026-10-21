<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('drinkwell:set-password {email : The account to change} {password : The new password, at least 12 characters}')]
#[Description('Local testing only: set the password of an existing account and say whether it can sign in')]
class SetPasswordCommand extends Command
{
    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->components->error('This command is for local testing only. On the live site, use Forgot password.');

            return self::FAILURE;
        }

        $email = strtolower(trim((string) $this->argument('email')));
        $password = (string) $this->argument('password');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->components->error("No account uses {$email}.");
            $this->line('Accounts you can sign in with: '.User::where('role', '!=', Role::Operator)->pluck('email')->implode(', '));

            return self::FAILURE;
        }

        if (mb_strlen($password) < 12) {
            $this->components->error('Use at least 12 characters.');

            return self::FAILURE;
        }

        $user->forceFill(['password' => $password, 'email_verified_at' => $user->email_verified_at ?? now()])->save();

        if ($user->role === Role::Operator) {
            $this->components->warn('Password saved, but this is a simulated co-partner account, and those can never sign in.');

            return self::SUCCESS;
        }

        $ok = Hash::check($password, $user->fresh()->password);
        $this->components->info($ok ? "Done. Sign in as {$email} with that password." : 'The password could not be saved.');
        $this->line('Role: '.$user->role->value.', status: '.$user->status->value);

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
