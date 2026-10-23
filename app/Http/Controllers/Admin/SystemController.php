<?php

namespace App\Http\Controllers\Admin;

use App\Console\Commands\TickCommand;
use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

/**
 * Admin, Settings, System. Shows whether the live site is healthy and installs updates after new files are
 * uploaded, so the owner never needs a terminal on cPanel.
 */
class SystemController extends Controller
{
    /** PHP releases that carry the fix for the PHP2Shell flaw, per branch. */
    private const PATCHED = ['8.2' => '8.2.35', '8.3' => '8.3.36', '8.4' => '8.4.27', '8.5' => '8.5.12'];

    public function show(): View
    {
        return view('admin.settings.system', ['checks' => $this->checks(), 'pending' => $this->pending()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $before = $this->pending();
        $out = [];
        try {
            Artisan::call('migrate', ['--force' => true]);
            $out[] = trim(Artisan::output());
            foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $command) {
                Artisan::call($command);
            }
            Artisan::call('storage:link');
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'The update stopped: '.$e->getMessage());
        }

        AuditLogger::log($request->user(), 'system.update', null, ['pending' => $before], ['pending' => $this->pending()]);

        return back()->with('status', $before === []
            ? 'Caches cleared. There were no database changes to install.'
            : 'Update installed: '.count($before).' database change(s) applied and caches cleared.');
    }

    /** @return list<string> Migration files that have not run yet. */
    private function pending(): array
    {
        try {
            $ran = DB::table('migrations')->pluck('migration')->all();
        } catch (Throwable) {
            return ['(the migrations table is missing)'];
        }
        $files = array_map(fn ($f) => basename($f, '.php'), glob(database_path('migrations/*.php')) ?: []);

        return array_values(array_diff($files, $ran));
    }

    /** @return list<array{label: string, ok: bool|null, note: string}> ok null means a warning. */
    private function checks(): array
    {
        $v = PHP_VERSION;
        $branch = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
        $safe = isset(self::PATCHED[$branch]) ? version_compare($v, self::PATCHED[$branch], '>=') : version_compare($v, '8.6.0', '>=');
        $tick = cache(TickCommand::KEY);
        $prod = app()->isProduction();

        return [
            ['label' => "PHP {$v}", 'ok' => version_compare($v, '8.3.0', '>=') && $safe ? true : null,
                'note' => $safe ? 'Up to date with the security fixes.' : 'Ask your host for PHP '.(self::PATCHED[$branch] ?? '8.4.27').' or newer, or pick a newer version in MultiPHP Manager.'],
            ['label' => 'Site address uses https', 'ok' => str_starts_with((string) config('app.url'), 'https://') ? true : null,
                'note' => 'Run AutoSSL in cPanel, then set APP_URL to the https address.'],
            ['label' => 'Error details are hidden', 'ok' => config('app.debug') ? false : true,
                'note' => 'Set APP_DEBUG=false in .env. Error pages would otherwise show secrets.'],
            ['label' => 'Emails are really sent', 'ok' => config('mail.default') === 'log' ? ($prod ? false : null) : true,
                'note' => 'Emails are only written to the log. Add your mailbox details to the MAIL_ lines in .env.'],
            ['label' => 'Sign-in codes stay private', 'ok' => config('drinkwell.show_codes') ? null : true,
                'note' => 'DEMO_SHOW_CODES is on, so codes show on screen. Fine for testing; set it to false before real members join.'],
            ['label' => 'The cron job is running', 'ok' => $tick !== null && now()->timestamp - (int) $tick <= 600 ? true : null,
                'note' => 'No run in the last 10 minutes. Add the cron job from the setup guide (every minute).'],
            ['label' => 'Database is up to date', 'ok' => $this->pending() === [] ? true : false,
                'note' => 'New files were uploaded. Press Install updates below.'],
            ['label' => 'The installer is removed', 'ok' => is_file(public_path('install.php')) ? null : true,
                'note' => 'Delete public/install.php in File Manager. It is locked, but it is safer gone.'],
        ];
    }
}
