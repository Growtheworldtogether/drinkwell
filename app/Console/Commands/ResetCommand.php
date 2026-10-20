<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

/**
 * Wipes the platform back to the seeded state (SPEC 11.4). Deliberately CLI only: there is no button for it.
 */
#[Signature('drinkwell:reset {--force : Run outside the local environment and skip the confirmation}')]
#[Description('Drop every table, migrate and seed again, and delete uploaded files (local environment only unless --force)')]
class ResetCommand extends Command
{
    /** Directories of uploads, by disk. Brand files sit at the root of the public disk and are removed by file. */
    private const UPLOADS = ['local' => []];

    public function handle(): int
    {
        if (! app()->isLocal() && ! $this->option('force')) {
            $this->components->error('Reset erases all data. It only runs when APP_ENV is local. Pass --force to override.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('This deletes every partner, machine and payout. Continue?')) {
            $this->components->warn('Nothing was changed.');

            return self::FAILURE;
        }

        foreach (self::UPLOADS as $disk => $directories) {
            foreach ($directories as $directory) {
                Storage::disk($disk)->deleteDirectory($directory);
            }
        }

        foreach (Storage::disk('public')->files() as $file) {
            if (preg_match('/\.(png|webp|jpe?g)$/i', $file)) {
                Storage::disk('public')->delete($file);
            }
        }

        Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true], $this->output);

        $this->components->info('The platform is back to its seeded state.');

        return self::SUCCESS;
    }
}
