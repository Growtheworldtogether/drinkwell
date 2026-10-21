# Run DRinkWeLL on your own computer (before cPanel)

You need: PHP 8.3 or newer, Composer, Node 20 or newer. Easiest on Windows/Mac: install Laravel Herd (gives PHP and Composer) plus Node from nodejs.org.

## Start it (about 5 minutes)
1. Unzip the project and open a terminal in the folder.
2. Make the settings file first: `cp .env.example .env` (Windows: `copy .env.example .env`).
3. In `.env` set `DB_CONNECTION=sqlite`, delete the other lines starting with `DB_`, and set `MAIL_MAILER=log`. Create an empty file `database/database.sqlite` (Mac/Linux: `touch database/database.sqlite`).
4. `composer install`
5. `php artisan key:generate`
6. `npm install` then `npm run build` (builds the fonts, gradient, map and chart; this step has not been run anywhere yet, so tell me if it errors).
7. `php artisan migrate --seed` (adds the demo machines, demo member and admin)
8. `php artisan serve` and open http://localhost:8000
Emails go to the log file `storage/logs/laravel.log` (set `MAIL_MAILER=log`); the sign-in codes appear there.

Logins (password `Demo-pass-123!`): member `demo.member@example.test`, admin `admin@example.test`.

## What to check
Public: home (colour changing background, footer gradient), How it works, Your earnings (calculator), Locations (map), Questions, About, Contact (send a message, check admin Email log), Terms, Privacy, "Bigger text" button.
Sign up: all steps, age 51+ rule, email code (from the log), choose commercial and enterprise tier.
Member: dashboard, sidebar menu, Partner and deposit (upload a proof file), Withdraw (request, enter code, cancel), Profile, Sign out.
Admin: Overview, Pairings (enter partner payment details, confirm deposit, demo tools), Withdrawals (approve with reference, decline with reason, on/off switch), Machines, Settings (general, branding), Audit log, Email log, Integrity.
Screens: shrink the browser window to phone width and to tablet width; nothing should scroll sideways, the menu button opens the sidebar, buttons line up, figures stay on one line.
Tests: `vendor/bin/pest` (should say 101 passed).
