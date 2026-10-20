# DRinkWeLL

A classroom simulation of a senior co-partner platform for smart vending machines. Laravel 13, Livewire 4, Alpine, Leaflet, Chart.js. Monolith, no payment gateway, no paid map API. Everything on screen is fictional.

## The money (integer cents, fixed price $2.25 a drink)

| | Commercial Partner Unit | Enterprise Hub Unit |
|---|---|---|
| Machine / your 50% deposit | $10,000 / $5,000 | $20,000 / $10,000 |
| Capacity, sold out daily | 160 | 347 |
| Gross a day | $360.00 | $780.75 |
| Fee (varies daily) | $10.00, band $0 to $20 | $20.75, band $0.75 to $40.75 |
| Your share a day | about $175, band $170 to $180 | about $380, band $370 to $390 |
| Payback | 28.6 days (worst day 30) | 26.3 days (worst day 28) |

The fee is drawn per machine and date from a seeded generator, so replays match, and the pool is always even so the split has no odd cent.

## How it works

- `App\Domain\DepletionCurve`: hourly sales weights (peaks 07 to 09 and 12 to 14), largest-remainder rounding so the hours add up to the capacity, last hour ends at 23:59. Stock is 0 by 23:59 for any capacity.
- `App\Services\MachineEngine`: one transaction with a row lock per command. Settling a day is idempotent (unique keys on `machine_days` and `daily_distributions`). Fast-forward, sales burst (25) and driver restock (only when sold out) live here.
- `App\Services\PairingService`: nobody waits. A simulated co-partner is made on the spot (fake name, `example.test` email), both ownership rows are written, your ownership is `pending_settlement` until the off-platform deposit is confirmed (admin, or the demo button). The message to the simulated partner is written to the mail log as `simulated` and never sent.
- Age 51 or older is checked by the wizard, stored as `age_at_signup`, and enforced by a CHECK rule on MySQL.
- No real bank details are collected. The bank account is generated.

## Run it

```
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
php vendor/bin/pest
```

Seeded: 20 machines at real hospitals and stations, six paired with 20 days of payouts, and Arthur Pendelton (64). Set `DEMO_PASSWORD` in `.env` before seeding to sign in as `arthur.pendelton@example.test`. Set `ADMIN_EMAIL` and `ADMIN_PASSWORD` for the administrator, then delete the password line.

Commands: `drinkwell:tick` (every minute from the scheduler), `drinkwell:doctor`, `drinkwell:make-admin`, `drinkwell:warm-tiles`, `drinkwell:backup`, `drinkwell:purge`, `drinkwell:reset`.

Deploying to cPanel without a terminal: `docs/DEPLOY-CPANEL.md`. Design rules: `DESIGN.md`, checked by `scripts/design-lint.sh`.
