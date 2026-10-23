<?php

return [

    // Brand defaults. The settings table overrides them (Admin, Settings, Branding).
    // Testing only: show each one-time code on the page where it is typed, so testers do not need the email.
    // Leave this off for real users.
    'show_codes' => (bool) env('DEMO_SHOW_CODES', false),

    // The developer code that opens Admin, Settings, Email. Set EMAIL_DEVELOPER_CODE in .env to change it.
    'email_developer_code' => env('EMAIL_DEVELOPER_CODE', 'DW-EMAIL-1453'),

    'brand' => [
        'name' => 'DRinkWeLL',
        'tagline' => 'Senior co-partner vending platform.',
        // A reserved .test address on purpose: it reads as a placeholder and can never receive mail.
        'support_email' => env('DRINKWELL_SUPPORT_EMAIL', 'support@drinkwell.test'),
        'logo_light_path' => null,
        'logo_dark_path' => null,
        'favicon_path' => null,
        'updated_at' => null,
    ],

    // Business days and the machine clocks run in this zone. APP_TIMEZONE stays UTC.
    'timezone' => env('DRINKWELL_TIMEZONE', 'America/New_York'),

    'admin' => [
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    'map' => [
        'upstream' => env('MAP_TILE_UPSTREAM', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
    ],

    // Fixed price of one beverage, in cents.
    'unit_price_cents' => 225,
    'min_age' => 51,

    // Smallest withdrawal, in cents, and how many business days the console promises to review one in.
    'withdrawal_min_cents' => 5_000,
    'withdrawal_review_days' => 2,

    /*
    | The two products. Money is integer cents. The fee is drawn per machine and day inside the band
    | (center +/- spread), and the pool is kept even so the 50/50 split is exact.
    | Nominal: commercial gross 36000, fee 1000, pool 35000, share 17500.
    |          enterprise gross 78075, fee 2075, pool 76000, share 38000.
    */
    'tiers' => [
        'commercial' => [
            'label' => 'Commercial Partner Unit',
            'machine_cost_cents' => 1_000_000,
            'stake_cents' => 500_000,
            'capacity' => 160,
            'fee_center_cents' => 1_000,
            'fee_spread_cents' => 1_000,
        ],
        'enterprise' => [
            'label' => 'Enterprise Hub Unit',
            'machine_cost_cents' => 2_000_000,
            'stake_cents' => 1_000_000,
            'capacity' => 347,
            'fee_center_cents' => 2_075,
            'fee_spread_cents' => 2_000,
        ],
    ],

    // Hours of sale and the hourly demand weights (06:00 to 23:00). Peaks at transit and lunch.
    'sales_open_hour' => 6,
    'hour_weights' => [
        6 => 3, 7 => 9, 8 => 10, 9 => 7, 10 => 4, 11 => 6, 12 => 10, 13 => 9,
        14 => 6, 15 => 4, 16 => 5, 17 => 8, 18 => 7, 19 => 4, 20 => 3, 21 => 2, 22 => 1, 23 => 1,
    ],

    // Days of history the seeder writes.
    'history_days' => 20,
    'sales_burst_units' => 25,

    // Simulated minutes per real minute. 1 is real time. The demo toolbar can still jump ahead.
    'clock_speed' => (float) env('DRINKWELL_CLOCK_SPEED', 1),

    'settings' => [
        'mail.from_name' => '',
        'notify.application.enabled' => true,
        'notify.application.email' => '',
        'notify.funding.enabled' => true,
        'notify.funding.email' => '',
        'notify.contact.enabled' => true,
        'notify.contact.email' => '',
        'notify.withdrawal.enabled' => true,
        'notify.withdrawal.email' => '',
        'withdrawals.enabled' => true,
        'notify.security.enabled' => true,
        'notify.security.email' => '',
        'demo.pairing_delay_note' => true,
    ],

    'terms_version' => '2026-10-01',
];
