<?php

namespace Database\Factories;

use App\Models\MailLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MailLog> */
class MailLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'to_email' => fake()->safeEmail(),
            'template' => 'otp_code',
            'subject' => 'Your code',
            'status' => 'sent',
        ];
    }
}
