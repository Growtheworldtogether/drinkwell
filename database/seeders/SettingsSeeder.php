<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\SettingsRepository;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $brand = config('drinkwell.brand');

        $defaults = config('drinkwell.settings') + [
            'brand.name' => $brand['name'],
            'brand.tagline' => $brand['tagline'],
            'brand.logo_light_path' => null,
            'brand.logo_dark_path' => null,
            'brand.favicon_path' => null,
            'brand.updated_at' => now()->toIso8601String(),
            'support_email' => $brand['support_email'],
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        app(SettingsRepository::class)->flush();
    }
}
