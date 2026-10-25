<?php

namespace App\Http\Controllers\Admin;

use App\Console\Commands\TickCommand;
use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\SettingsRepository;
use App\Services\Updater;
use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    private const LATEST_KEY = 'updates.latest';

    private const LAST_KEY = 'update_last';

    public function show(Updater $updater, SettingsRepository $settings): View
    {
        $latest = Cache::get(self::LATEST_KEY);
        $current = $updater->current();

        return view('admin.settings.system', [
            'checks' => $this->checks(),
            'pending' => $this->pending(),
            'updates' => [
                'current' => $current,
                'latest' => $latest,
                'newer' => is_array($latest) && $updater->isNewer($latest, $current),
                'backup' => $updater->lastBackup(),
                'last' => $settings->array(self::LAST_KEY),
                'repo' => (string) config('drinkwell.updates.repo'),
                'job' => $updater->job(),
            ],
        ]);
    }

    /** "Check for updates": asks GitHub for the newest release and remembers it for the page. */
    public function check(Updater $updater): RedirectResponse
    {
        try {
            $latest = $updater->latest();
        } catch (Throwable $e) {
            return back()->with('error', 'Could not check for updates: '.$e->getMessage());
        }
        Cache::put(self::LATEST_KEY, $latest, now()->addDay());

        return back()->with('status', $updater->isNewer($latest)
            ? 'A new version is ready to install: the version of '.self::versionName($latest['tag']).'.'
            : 'You have the latest version.');
    }

    /** "Install update": starts the update job; the progress page runs it in short steps. */
    public function install(Request $request, Updater $updater): RedirectResponse
    {
        if (! $this->codeOk($request)) {
            return back()->withErrors(['developer_code' => 'That developer code is not right.'], 'update');
        }
        try {
            $latest = Cache::get(self::LATEST_KEY);
            $updater->start(is_array($latest) ? $latest : $updater->latest());
        } catch (Throwable $e) {
            return back()->with('error', 'The update did not start: '.$e->getMessage());
        }

        return redirect()->route('admin.settings.system.progress');
    }

    /**
     * Runs the next short step of the update and shows how far it is, then reloads itself. Download and compare
     * leave the site up; only the write step shows members "Back in a minute" (the admin passes through). The last
     * step runs on the new code: database changes, fresh caches, site back up.
     */
    public function progress(Request $request, Updater $updater, SettingsRepository $settings): Response|RedirectResponse
    {
        $job = $updater->job();
        if (! $job) {
            return redirect()->route('admin.settings.system');
        }
        if ($job['phase'] === 'finish') {
            return $this->finishJob($request, $updater, $settings, $job);
        }

        $cookie = null;
        try {
            if ($job['phase'] === 'write' && ! app()->isDownForMaintenance()) {
                $secret = Str::random(40);
                Artisan::call('down', ['--secret' => $secret, '--retry' => 30, '--refresh' => 20]);
                $cookie = MaintenanceModeBypassCookie::create($secret);
            }
            $job = $updater->advance($job);
        } catch (Throwable $e) {
            report($e);
            $updater->clearJob();
            if (app()->isDownForMaintenance()) {
                Artisan::call('up');
            }

            return redirect()->route('admin.settings.system')->with('error', 'The update was not installed, and the site is as it was: '.$e->getMessage());
        }

        $labels = ['download' => 'Downloading the new version', 'compare' => 'Checking which files changed', 'write' => 'Installing the new files', 'finish' => 'Finishing up'];
        $response = response()->view('admin.settings.update-progress', [
            'percent' => $updater->percent($job),
            'label' => $labels[$job['phase']] ?? 'Updating',
            'next' => route('admin.settings.system.progress'),
        ]);

        return $cookie ? $response->withCookie($cookie) : $response;
    }

    /** "Restore previous version": puts back the files the last update changed. */
    public function restore(Request $request, Updater $updater): RedirectResponse|Response
    {
        if (! $this->codeOk($request)) {
            return back()->withErrors(['developer_code' => 'That developer code is not right.'], 'restore');
        }
        $backup = $updater->lastBackup();
        if (! $backup || $backup['restored_at']) {
            return back()->with('error', 'There is no update to undo.');
        }

        $secret = Str::random(40);
        Artisan::call('down', ['--secret' => $secret, '--retry' => 30, '--refresh' => 20]);
        try {
            $updater->restore($backup['path']);
        } catch (Throwable $e) {
            report($e);
            Artisan::call('up');

            return back()->with('error', 'Could not restore: '.$e->getMessage());
        }
        $updater->saveJob(['phase' => 'finish', 'action' => 'restore', 'from' => $backup['to'], 'to' => $backup['from']]);

        return redirect()->route('admin.settings.system.progress')->withCookie(MaintenanceModeBypassCookie::create($secret));
    }

    /** An update that was interrupted and not continued: undo what it wrote and bring the site back. */
    public function cancel(Request $request, Updater $updater): RedirectResponse
    {
        $job = $updater->job();
        if ($job && in_array($job['phase'], ['write', 'finish'], true) && ! empty($job['backup']) && ($job['action'] ?? 'update') === 'update') {
            $updater->restore($job['backup']);
        }
        $updater->clearJob();
        foreach (['config:clear', 'route:clear', 'view:clear'] as $command) {
            try {
                Artisan::call($command);
            } catch (Throwable) {
            }
        }
        Artisan::call('up');
        AuditLogger::log($request->user(), 'system.update_cancelled', null, null, ['phase' => $job['phase'] ?? null]);

        return redirect()->route('admin.settings.system')->with('status', 'The update was stopped and the site is as it was before.');
    }

    /** Last step, on the new code: database changes, fresh caches, site back up. */
    private function finishJob(Request $request, Updater $updater, SettingsRepository $settings, array $job): RedirectResponse
    {
        $problem = null;
        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (Throwable $e) {
            report($e);
            $problem = $e->getMessage();
        }
        foreach (['config:clear', 'route:clear', 'view:clear', 'cache:clear'] as $command) {
            try {
                Artisan::call($command);
            } catch (Throwable) {
                // A cache that cannot be cleared is not a reason to keep the site down.
            }
        }
        Artisan::call('up');
        $updater->clearJob();

        $action = $job['action'] ?? 'update';
        $changed = 0;
        $added = 0;
        if ($action === 'update' && ! empty($job['backup']) && is_file($job['backup'].'/manifest.json')) {
            $m = json_decode((string) file_get_contents($job['backup'].'/manifest.json'), true);
            $changed = count($m['changed'] ?? []);
            $added = count($m['added'] ?? []);
        }
        $settings->set(self::LAST_KEY, ['action' => $action, 'to' => $job['to'] ?? '', 'at' => now()->toIso8601String(), 'problem' => $problem], $request->user());
        AuditLogger::log($request->user(), 'system.'.$action, null, ['version' => $job['from'] ?? null], ['version' => $job['to'] ?? null, 'changed' => $changed, 'added' => $added, 'problem' => $problem]);

        if ($problem !== null) {
            return redirect()->route('admin.settings.system')->with('error', 'The new files are in place, but the database changes stopped: '.$problem.' You can press Restore previous version.');
        }

        return redirect()->route('admin.settings.system')->with('status', $action === 'restore'
            ? 'The previous version, '.self::versionName((string) $job['to']).', is back.'
            : 'Updated to the version of '.self::versionName((string) $job['to']).'. '.$changed.' file(s) replaced and '.$added.' added. Your settings, uploads and database were kept.');
    }

    /** "release-20261025-1935" as "Oct 25, 2026 3:35 pm" in the site's time zone; other names are shown as they are. */
    public static function versionName(string $tag): string
    {
        if (! preg_match('/^release-(\d{4})(\d{2})(\d{2})-(\d{2})(\d{2})$/', $tag, $m)) {
            return $tag;
        }

        return \Illuminate\Support\Carbon::create((int) $m[1], (int) $m[2], (int) $m[3], (int) $m[4], (int) $m[5], 0, 'UTC')
            ->timezone(config('app.timezone'))->format('M j, Y g:i a');
    }


    private function codeOk(Request $request): bool
    {
        $typed = strtoupper(preg_replace('/\s+/', '', (string) $request->input('developer_code')) ?? '');
        $code = strtoupper((string) config('drinkwell.email_developer_code'));

        return $code !== '' && hash_equals($code, $typed);
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
            try {
                Artisan::call('storage:link');
            } catch (Throwable) {
                // Some hosts do not allow links; uploaded logos are the only thing that needs it.
            }
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
                'note' => 'Emails are only written to the log. Connect your mailbox in Settings, Email.'],
            ['label' => 'Sign-in codes stay private', 'ok' => config('drinkwell.show_codes') ? null : true,
                'note' => 'DEMO_SHOW_CODES is on, so codes show on screen. Fine for testing; set it to false before real members join.'],
            ['label' => 'The cron job is running', 'ok' => $tick !== null && now()->timestamp - (int) $tick <= 600 ? true : null,
                'note' => 'No run in the last 10 minutes. Add the cron job from the setup guide (every minute).'],
            ['label' => 'Database is up to date', 'ok' => $this->pending() === [] ? true : false,
                'note' => 'New files were uploaded. Press Install updates below.'],
            ['label' => 'The installer is removed', 'ok' => is_file(public_path('install.php')) ? null : true,
                'note' => 'Delete install.php in File Manager (in public_html, or drinkwell/public). It is locked, but it is safer gone.'],
            $this->autoPairCheck(),
        ];
    }

    /** @return array{label: string, ok: bool|null, note: string} Why new members are or are not paired automatically. */
    private function autoPairCheck(): array
    {
        $label = 'Automatic pairing';
        if (! is_file(resource_path('views/onboard/_searching.blade.php')) || ! \Illuminate\Support\Facades\Route::has('onboard.match')) {
            return ['label' => $label, 'ok' => false, 'note' => 'Its files are not all in place. Upload the update again into your home folder, then press Install updates.'];
        }
        if (! app(\App\Services\SettingsRepository::class)->bool(SignupSettingsController::AUTO_PAIR_KEY, true)) {
            return ['label' => $label.' is off', 'ok' => null, 'note' => 'Members wait for you to pair them. Turn it on in Settings, Deposit details.'];
        }
        $missing = SignupSettingsController::missingForAuto();
        if ($missing !== []) {
            return ['label' => $label, 'ok' => false, 'note' => 'Waiting for these default details: '.implode(', ', $missing).'. Fill them in under Settings, Deposit details.'];
        }
        $full = [];
        foreach (\App\Enums\Tier::cases() as $tier) {
            $open = \App\Models\VendingMachine::query()->where('tier', $tier)->where('assignment_status', \App\Enums\AssignmentStatus::Open)->count();
            if ($open === 0) {
                $full[] = $tier->label();
            }
        }
        if ($full !== []) {
            return ['label' => $label, 'ok' => null, 'note' => 'No free machine for: '.implode(', ', $full).'. Members who choose it wait for you.'];
        }

        return ['label' => $label.' is on', 'ok' => true, 'note' => ''];
    }
}
