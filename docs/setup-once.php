<?php
/**
 * One-time setup page for hosts with no terminal.
 *
 * 1. Add a line to .env:   SETUP_KEY=some-long-random-text-you-make-up
 * 2. Upload this file into the public folder (next to index.php).
 * 3. Open  https://yourdomain.com/setup-once.php?key=some-long-random-text-you-make-up
 * 4. When it says finished, it deletes itself. Then remove SETUP_KEY and ADMIN_PASSWORD from .env.
 *
 * Add &action=doctor to the address to run the health check instead.
 */

set_time_limit(0);
header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex');

// Find the app: public folder sits inside the app, or its files were copied to public_html.
$base = null;
foreach ([__DIR__, __DIR__.'/..', __DIR__.'/../drinkwell'] as $candidate) {
    if (is_file($candidate.'/vendor/autoload.php') && is_file($candidate.'/bootstrap/app.php')) {
        $base = realpath($candidate);
        break;
    }
}

if ($base === null) {
    http_response_code(500);
    exit("Cannot find the app (vendor/autoload.php). Check where you uploaded the files.\n");
}

// Read SETUP_KEY straight from .env, before anything else runs.
$expected = '';
if (is_file($base.'/.env')) {
    foreach (file($base.'/.env', FILE_IGNORE_NEW_LINES) as $line) {
        if (preg_match('/^\s*SETUP_KEY\s*=\s*(.*)$/', $line, $m)) {
            $expected = trim($m[1], " \t\"'");
        }
    }
}

$given = (string) ($_GET['key'] ?? '');

if (strlen($expected) < 12) {
    http_response_code(403);
    exit("Add a line SETUP_KEY=... (at least 12 characters) to your .env file first.\n");
}

if (! hash_equals($expected, $given)) {
    http_response_code(403);
    exit("Wrong key.\n");
}

// The cache and session tables do not exist yet, so for this one run keep both in files.
foreach (['CACHE_STORE' => 'file', 'SESSION_DRIVER' => 'file'] as $name => $value) {
    putenv("{$name}={$value}");
    $_ENV[$name] = $_SERVER[$name] = $value;
}

require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$run = function (string $command, array $args = []) use ($kernel): int {
    echo "=== php artisan {$command} ===\n";
    try {
        $code = $kernel->call($command, $args);
    } catch (Throwable $e) {
        echo 'ERROR: '.$e->getMessage()."\n\n";

        return 1;
    }
    echo trim($kernel->output())."\n\n";

    return $code;
};

if (($_GET['action'] ?? '') === 'doctor') {
    $run('drinkwell:doctor');
    exit("Health check done. This page is still here. Delete setup-once.php when you are finished.\n");
}

$failed = 0;
$failed += $run('migrate', ['--force' => true]);

if ($failed === 0) {
    $failed += $run('db:seed', ['--force' => true]);
}

// The storage link can be refused by some hosts. That is not fatal for the first run.
if (! is_link(__DIR__.'/storage') && ! file_exists(__DIR__.'/storage')) {
    $run('storage:link');
}

if ($failed > 0) {
    exit("Something failed. Read the message above. This page was NOT deleted so you can try again.\n");
}

$run('config:clear');

@unlink(__FILE__);
echo is_file(__FILE__)
    ? "Finished, but this file could not delete itself. Delete setup-once.php by hand in File Manager now.\n"
    : "Finished. This page deleted itself.\n";
echo "Now open .env and remove the SETUP_KEY and ADMIN_PASSWORD lines.\n";
