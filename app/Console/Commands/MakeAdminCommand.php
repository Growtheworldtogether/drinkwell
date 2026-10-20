<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use App\Support\PasswordRules;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('drinkwell:make-admin {name : Full name} {email : Sign-in email}')]
#[Description('Create an administrator (role admin, email verified). Prompts for the password.')]
class MakeAdminCommand extends Command
{
    public function handle(): int
    {
        $password = (string) $this->secret('Password (minimum 12 characters)');
        $confirmation = (string) $this->secret('Confirm password');

        $validator = Validator::make([
            'name' => $this->argument('name'),
            'email' => strtolower(trim((string) $this->argument('email'))),
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRules::for(Role::Admin)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $data = $validator->validated();

        $admin = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => Role::Admin,
            'status' => UserStatus::Active,
        ]);

        // Not mass-assignable on purpose: only trusted code marks an email as verified.
        $admin->forceFill(['email_verified_at' => now()])->save();

        $this->components->info("Administrator {$data['email']} created. Sign in at /login with email and password.");

        return self::SUCCESS;
    }
}
