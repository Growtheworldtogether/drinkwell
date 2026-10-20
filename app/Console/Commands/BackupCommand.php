<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

/**
 * A database backup into storage/app/backups (SPEC 17.2). SQLite is copied; MySQL and MariaDB are dumped with
 * mysqldump. The file is readable by the owner only and the folder keeps the newest 14.
 */
#[Signature('drinkwell:backup')]
#[Description('Back up the database to storage/app/backups (mode 0600, newest 14 kept)')]
class BackupCommand extends Command
{
    public function handle(): int
    {
        $dir = storage_path('app/backups');
        if (! is_dir($dir) && ! mkdir($dir, 0700, true) && ! is_dir($dir)) {
            $this->components->error('Could not create '.$dir);

            return self::FAILURE;
        }

        $connection = (string) config('database.default');
        $config = (array) config("database.connections.{$connection}");
        $stamp = now()->format('Ymd-His');

        try {
            $file = match ($config['driver'] ?? null) {
                'sqlite' => $this->sqlite($config, $dir, $stamp),
                'mysql', 'mariadb' => $this->mysql($config, $dir, $stamp),
                default => throw new \RuntimeException('Backups are not built for the '.($config['driver'] ?? 'unknown').' driver.'),
            };
        } catch (\Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        chmod($file, 0600);
        $this->prune($dir);
        $this->components->info('Backup written to '.$file);

        return self::SUCCESS;
    }

    private function sqlite(array $config, string $dir, string $stamp): string
    {
        $source = (string) $config['database'];
        $target = "{$dir}/db-{$stamp}.sqlite";

        if ($source === ':memory:') {
            // A consistent copy of an in-memory database, used by the tests.
            DB::statement('VACUUM INTO '.DB::getPdo()->quote($target));

            return $target;
        }

        if (! is_file($source) || ! copy($source, $target)) {
            throw new \RuntimeException('Could not copy the SQLite file.');
        }

        return $target;
    }

    private function mysql(array $config, string $dir, string $stamp): string
    {
        $target = "{$dir}/db-{$stamp}.sql";
        $process = new Process(['mysqldump', '--single-transaction', '--host='.$config['host'], '--port='.$config['port'], '--user='.$config['username'], '--result-file='.$target, $config['database']], null, ['MYSQL_PWD' => (string) $config['password']]);
        $process->setTimeout(600)->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('mysqldump failed: '.trim($process->getErrorOutput()));
        }

        return $target;
    }

    private function prune(string $dir): void
    {
        $files = glob($dir.'/db-*') ?: [];
        rsort($files);

        foreach (array_slice($files, (int) config('security.backups_keep')) as $old) {
            @unlink($old);
        }
    }
}
