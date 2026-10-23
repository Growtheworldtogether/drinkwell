<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\VendingMachine;
use App\Models\User;
use App\Console\Commands\TickCommand;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

#[Signature('drinkwell:doctor')]
#[Description('Check that this installation is ready: settings, database, writable folders, storage link, built assets, mail, scheduler')]
class DoctorCommand extends Command
{
    private int $failed = 0;

    private int $warned = 0;

    /** The checks the database enforces on MySQL and MariaDB: table, rule. SQLite does not enforce them, so rows are scanned here. */
    private function rules(): array
    {
        return [
            ['partner_profiles', '`age_at_signup` >= '.(int) config('drinkwell.min_age')],
            ['vending_machines', '`capacity` IN (160, 347)'],
            ['vending_machines', '`stock` <= `capacity`'],
            ['vending_machines', '`day_minute` BETWEEN 0 AND 1439'],
            ['machine_ownerships', '`slot_no` IN (1, 2)'],
            ['machine_days', '`pool_cents` = `gross_cents` - `fee_cents` AND `pool_cents` % 2 = 0'],
        ];
    }

    public function handle(): int
    {
        $prod = app()->isProduction();

        $this->components->info('DRinkWeLL doctor');

        // Settings.
        $this->line('Settings');
        $this->row(config('app.key') ? true : 'fail', 'APP_KEY is set', 'Run: php artisan key:generate --force');
        $this->row($prod ? true : 'warn', 'APP_ENV is production', 'APP_ENV is "'.app()->environment().'". Set APP_ENV=production on the live site.');
        $this->row(! ($prod && config('app.debug')) ? (config('app.debug') ? 'warn' : true) : 'fail', 'APP_DEBUG is off', 'Set APP_DEBUG=false. Debug pages show secrets.');
        $this->row(str_starts_with((string) config('app.url'), 'https://') ? true : ($prod ? 'warn' : true), 'APP_URL uses https', 'Set APP_URL to the https:// address of the site.');
        $this->row(in_array(config('session.driver'), ['array'], true) ? 'fail' : true, 'Sessions are stored ('.config('session.driver').')', 'Use SESSION_DRIVER=database or file.');
        $this->row(config('queue.default') === 'sync' ? true : 'warn', 'Queue is "sync" (no worker needed)', 'QUEUE_CONNECTION is "'.config('queue.default').'". Nothing here is queued; use sync on shared hosting.');
        $this->row($prod && config('mail.default') === 'log' ? 'fail' : true, 'Mail is not the log mailer in production', 'Set MAIL_MAILER=smtp and the MAIL_* values.');
        $this->row(! env('ADMIN_PASSWORD') || ! $prod ? true : 'warn', 'ADMIN_PASSWORD is removed from .env', 'After the first seed, delete the ADMIN_PASSWORD line from .env.');

        // PHP.
        $this->line('PHP');
        $this->row(version_compare(PHP_VERSION, '8.3.0', '>=') ? true : 'fail', 'PHP '.PHP_VERSION.' (8.3 or newer)', 'Choose PHP 8.3 or 8.4 in cPanel, MultiPHP Manager.');
        $needed = ['mbstring', 'openssl', 'curl', 'fileinfo', 'gd', 'tokenizer', 'xml', 'ctype', 'pdo'];
        if (config('database.default') !== 'sqlite') {
            $needed[] = 'pdo_mysql';
        }
        $missing = array_values(array_filter($needed, fn ($e) => ! extension_loaded($e)));
        $this->row($missing === [] ? true : 'fail', 'PHP extensions present', 'Missing: '.implode(', ', $missing).'. Tick them in cPanel, Select PHP Version, Extensions.');

        // Database.
        $this->line('Database');
        try {
            $driver = DB::getDriverName();
            $version = (string) (DB::selectOne(DB::getDriverName() === 'sqlite' ? 'select sqlite_version() as v' : 'select version() as v')->v ?? '');
            $this->row(true, "Connected ({$driver} {$version})", '');
        } catch (Throwable $e) {
            $this->row('fail', 'Connected', 'Cannot connect: '.$e->getMessage());
            $this->summary();

            return self::FAILURE;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $maria = stripos($version, 'mariadb') !== false;
            $number = (string) preg_replace('/[^0-9.].*$/', '', preg_replace('/^5\.5\.5-/', '', $version));
            $ok = $maria ? version_compare($number, '10.3.0', '>=') : version_compare($number, '8.0.16', '>=');
            $this->row($ok ? true : 'fail', 'Server is new enough ('.($maria ? 'MariaDB 10.3+' : 'MySQL 8.0.16+').')', 'Ask the host for MySQL 8.0.16+ or MariaDB 10.3+. Older versions ignore the safety CHECK rules.');
        }

        try {
            $files = count(glob(database_path('migrations/*.php')) ?: []);
            $ran = DB::table('migrations')->count();
            $this->row($ran >= $files ? true : 'fail', "Migrations ran ({$ran} of {$files})", 'Run: php artisan migrate --force');
        } catch (Throwable) {
            $this->row('fail', 'Migrations ran', 'The tables are missing. Run: php artisan migrate --force');
            $this->summary();

            return self::FAILURE;
        }

        $this->row(VendingMachine::query()->count() > 0 ? true : 'fail', 'Machines seeded', 'Run: php artisan db:seed --force');
        $this->row(User::query()->where('role', Role::Admin)->exists() ? true : 'fail', 'An administrator exists', 'Run: php artisan drinkwell:make-admin "Your Name" you@example.com');

        // Rows against the database CHECK rules.
        $bad = [];
        foreach ($this->rules() as [$table, $rule]) {
            $n = (int) DB::table($table)->whereRaw("NOT ({$rule})")->count();
            if ($n > 0) {
                $bad[] = "{$table}: {$n} row(s) break \"{$rule}\"";
            }
        }
        $this->row($bad === [] ? true : 'fail', 'Every row passes the database CHECK rules', implode('; ', $bad));

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $checks = (int) DB::table('information_schema.CHECK_CONSTRAINTS')->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())->count();
            $this->row($checks >= count($this->rules()) ? true : 'warn', "CHECK rules exist in the database ({$checks})", 'Expected '.count($this->rules()).'. The migrations may have run on an older server.');
        }

        // Folders and files.
        $this->line('Files');
        foreach (['storage', 'storage/logs', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'bootstrap/cache'] as $dir) {
            $this->row(is_dir(base_path($dir)) && is_writable(base_path($dir)) ? true : 'fail', "{$dir} is writable", "Set the folder permissions to 755 (775 if needed) and make sure your cPanel user owns it.");
        }
        $link = public_path('storage');
        $this->row(is_link($link) || is_dir($link) ? true : 'warn', 'public/storage link exists', 'Run: php artisan storage:link (or make the link by hand, see the guide). Uploaded logos need it.');
        $this->row(is_file(public_path('build/manifest.json')) ? true : 'fail', 'Site assets are built (public/build)', 'Run npm run build on your computer and upload the public/build folder.');
        $tiles = is_dir(storage_path('app/tiles')) ? count(File::allFiles(storage_path('app/tiles'))) : 0;
        $this->row($tiles > 0 ? true : 'warn', "Map tiles are cached ({$tiles})", 'Run: php artisan drinkwell:warm-tiles (needs outbound internet).');

        // Scheduler.
        $this->line('Scheduler');
        $at = cache(TickCommand::KEY);
        $fresh = $at !== null && now()->timestamp - (int) $at <= 600;
        $this->row($fresh ? true : 'warn', 'The scheduler ran in the last 10 minutes', 'No recent run. Add the cPanel cron job that runs "php artisan schedule:run" every minute.');

        $this->summary();

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** @param  true|'warn'|'fail'  $result */
    private function row(bool|string $result, string $label, string $fix): void
    {
        if ($result === true) {
            $this->line("  <fg=green>PASS</> {$label}");

            return;
        }

        if ($result === 'warn') {
            $this->warned++;
            $this->line("  <fg=yellow>WARN</> {$label}");
        } else {
            $this->failed++;
            $this->line("  <fg=red>FAIL</> {$label}");
        }

        if ($fix !== '') {
            $this->line("       {$fix}");
        }
    }

    private function summary(): void
    {
        $this->newLine();

        if ($this->failed > 0) {
            $this->components->error("{$this->failed} problem(s) to fix, {$this->warned} warning(s).");
        } elseif ($this->warned > 0) {
            $this->components->warn("Ready, with {$this->warned} warning(s).");
        } else {
            $this->components->info('Everything is ready.');
        }
    }
}
