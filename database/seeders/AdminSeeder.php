<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('drinkwell.admin.email');
        $password = config('drinkwell.admin.password');

        if (! $email || ! $password) {
            if (app()->isProduction()) {
                throw new RuntimeException('ADMIN_EMAIL and ADMIN_PASSWORD must be set to seed the administrator in production.');
            }

            $this->command?->warn('ADMIN_EMAIL or ADMIN_PASSWORD is not set. No administrator was created.');

            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator',
                'password' => $password,
                'role' => Role::Admin,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        );
    }
}
