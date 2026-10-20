<?php

namespace Database\Factories;

use App\Models\PayoutAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayoutAccount> */
class PayoutAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'holder_name' => fake()->name(),
            'bank_name' => 'Test Bank',
            'routing_number' => '021000021',
            'account_number' => '000123456789',
            'last4' => '6789',
            'is_default' => true,
        ];
    }
}
