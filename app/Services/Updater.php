<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * One-click updates from the GitHub Releases page (Admin, Settings, System).
 *
 * The site downloads the release zip made for its own layout, checks it against GitHub's sha256 digest, backs up
 * every file it is about to change, and writes the new files in place. Settings (.env), uploads (storage), the
 * database and the installer are never touched. Database changes and cache clearing run afterwards in a fresh
 * request (finish), so they use the new code. A failed install puts the backed-up files back.
 */
class Updater
{
    /** Never written by an update: the site's own settings, data, caches and the installer. */
    private string $base;

    private string $public;

    public function __construct(?string $base = null, ?string $public = null)
    {
        $this->base = rtrim($base ?? base_path(), '/');
        $this->public = rtrim($public ?? public_path(), '/');
    }

    private const KEEP = ['#^\.env$#', '#^\.env\.(?!example$|production\.example$)#', '#^storage/#', '#^bootstrap/cache/#', '#^public/install\.php$#', '#^public/storage(/|$)#', '#\.sqlite$#'];

    public function backupRoot(): string
    {
        return $this->base.'/storage/app/update-backups';
    }

    /** @return array{tag: string, commit: string, built_at: string}|null The version installed now, if recorded. */
    public function current(): ?array
    {
        $file = $this->base.'/version.json';
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        return is_array($data) && isset($data['tag']) ? $data + ['commit' => '', 'built_at' => ''] : null;
    }

    /** "public_html" when the public files live in a folder beside the app (shared hosting), otherwise "standard". */
    public function layout(): string
    {
        return realpath($this->public) !== realpath($this->base.'/public') ? 'public_html' : 'standard';
    }

    public function assetName(): string
    {
        return $this->layout() === 'public_html' ? 'drinkwell-release-public_html.zip' : 'drinkwell-release.zip';
    }

    /**
     * The newest release on GitHub that has a package for this site's layout.
     *
     * @return array{tag: string, title: string, published_at: string, notes: string, asset_id: int, size: int, digest: ?string}
     */
    public function latest(): array
    {
        $repo = (string) config('drinkwell.updates.repo');
        $response = $this->github()->get("https://api.github.com/repos/{$repo}/releases/latest");
        if ($response->status() === 404) {
            throw new RuntimeException("No releases were found for {$repo}. On GitHub, open Actions, Build release zip, and run it once.");
        }
        if (! $response->successful()) {
            throw new RuntimeException('GitHub answered '.$response->status().'. Try again in a few minutes.');
        }
        $release = $response->json();
        $asset = collect($release['assets'] ?? [])->firstWhere('name', $this->assetName());
        if (! $asset) {
            throw new RuntimeException('The newest release has no '.$this->assetName().'. Run Build release zip again on GitHub.');
        }

        return [
            'tag' => (string) $release['tag_name'],
            'title' => (string) ($release['name'] ?? $release['tag_name']),
            'published_at' => (string) ($release['published_at'] ?? ''),
            'notes' => (string) ($release['body'] ?? ''),
            'asset_id' => (int) $asset['id'],
            'size' => (int) ($asset['size'] ?? 0),
            'digest' => isset($asset['digest']) && str_starts_with((string) $asset['digest'], 'sha256:') ? substr((string) $asset['digest'], 7) : null,
        ];
    }

    /** True when $latest is newer than what is installed (or nothing is recorded yet). */
    public function isNewer(array $latest, ?array $current = null): bool
    {
        $current ??= $this->current();

        return $current === null || strcmp($latest['tag'], (string) $current['tag']) > 0;
    }

    /** Downloads the release package and checks it. @return string Path of the checked zip. */
    public function download(array $latest): string
    {
        $dir = $this->base.'/storage/app/updates';
        File::ensureDirectoryExists($dir);
        foreach (glob($dir.'/*.zip') ?: [] as $old) {
            @unlink($old);
        }
        $path = $dir.'/'.preg_replace('/[^A-Za-z0-9._-]/', '', $latest['tag']).'.zip';
        $repo = (string) config('drinkwell.updates.repo');

        $response = $this->github('application/octet-stream')->timeout(600)->sink($path)
            ->get("https://api.github.com/repos/{$repo}/releases/assets/{$latest['asset_id']}");
        if (! $response->successful() || ! is_file($path)) {
            @unlink($path);
            throw new RuntimeException('The download did not finish (GitHub answered '.$response->status().'). Try again.');
        }
        if ($latest['digest'] !== null && ! hash_equals($latest['digest'], hash_file('sha256', $path))) {
            @unlink($path);
            throw new RuntimeException('The download does not match the fingerprint GitHub published, so it was not used. Try again.');
        }

        $this->inspect($path, $latest['tag']);

        return $path;
    }

    /** Checks a package fits this site: right layout, version inside matching, no unsafe paths. */
    public function inspect(string $zipPath, ?string $expectTag = null): array
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('The package is not a readable zip file.');
        }
        try {
            $prefix = $this->layout() === 'public_html' ? 'drinkwell/' : '';
            $version = $zip->getFromName($prefix.'version.json');
            $info = $version !== false ? json_decode($version, true) : null;
            if (! is_array($info) || ! isset($info['tag'])) {
                throw new RuntimeException('The package has no version information. Use a zip from the Releases page, made by Build release zip.');
            }
            if ($expectTag !== null && $info['tag'] !== $expectTag) {
                throw new RuntimeException("The package says it is {$info['tag']}, not {$expectTag}.");
            }
            if ($zip->locateName($prefix.'artisan') === false || $zip->locateName($prefix.'vendor/autoload.php') === false) {
                throw new RuntimeException('The package does not fit this site\'s folder layout.');
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                if (str_contains($name, '..') || str_starts_with($name, '/') || str_contains($name, '\\') || preg_match('/^[A-Za-z]:/', $name)) {
                    throw new RuntimeException('The package contains an unsafe path and was not used.');
                }
            }
        } finally {
            $zip->close();
        }

        return $info;
    }

    /**
     * Writes the package over the site. Only files that differ are written; each one replaced is copied to a backup
     * first, and new files are listed, so restore() can undo it exactly. Fails as a whole: on any error the backup
     * is put back before the error is passed on.
     *
     * @return array{backup: string, changed: int, added: int, unchanged: int, to: string}
     */
    public function install(string $zipPath): array
    {
        @set_time_limit(900);
        @ignore_user_abort(true);
        $info = $this->inspect($zipPath);
        $from = $this->current()['tag'] ?? 'unknown';

        foreach ([$this->base, $this->public] as $dir) {
            if (! is_writable($dir)) {
                throw new RuntimeException("The folder {$dir} cannot be written to. In File Manager, set its permissions to 755.");
            }
        }

        $backup = $this->backupRoot().'/'.gmdate('Ymd-His');
        File::ensureDirectoryExists($backup.'/files');
        $manifest = ['from' => $from, 'to' => $info['tag'], 'created_at' => gmdate('c'), 'layout' => $this->layout(), 'changed' => [], 'added' => []];
        // By reference: the list grows as files are written, and must be saved as it is at each step.
        $writeManifest = function () use (&$manifest, $backup) {
            file_put_contents($backup.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        };
        $writeManifest();

        $zip = new ZipArchive;
        $zip->open($zipPath);
        $unchanged = 0;
        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                if (str_ends_with($name, '/')) {
                    continue;
                }
                $logical = $this->logical($name);
                if ($logical === null || $this->kept($logical)) {
                    continue;
                }
                $target = $this->target($logical);
                $stat = $zip->statIndex($i);

                if (is_file($target) && filesize($target) === $stat['size'] && hash_file('crc32b', $target) === sprintf('%08x', $stat['crc'])) {
                    $unchanged++;

                    continue;
                }

                if (is_file($target)) {
                    $copy = $backup.'/files/'.$logical;
                    File::ensureDirectoryExists(dirname($copy));
                    if (! @copy($target, $copy)) {
                        throw new RuntimeException("Could not back up {$logical}.");
                    }
                    $manifest['changed'][] = $logical;
                } else {
                    $manifest['added'][] = $logical;
                }
                $writeManifest();

                File::ensureDirectoryExists(dirname($target));
                $tmp = $target.'.dw-new';
                $in = $zip->getStream($name);
                if ($in === false || file_put_contents($tmp, $in) === false) {
                    @unlink($tmp);
                    throw new RuntimeException("Could not write {$logical}.");
                }
                if (is_resource($in)) {
                    fclose($in);
                }
                if (! @rename($tmp, $target)) {
                    @unlink($tmp);
                    throw new RuntimeException("Could not replace {$logical}.");
                }
            }
        } catch (Throwable $e) {
            $zip->close();
            $this->restore($backup);
            throw $e;
        }
        $zip->close();
        $this->resetOpcache();
        $this->pruneBackups(3);

        return ['backup' => $backup, 'changed' => count($manifest['changed']), 'added' => count($manifest['added']), 'unchanged' => $unchanged, 'to' => $info['tag']];
    }

    /** Puts back every file an update changed and removes the files it added. */
    public function restore(string $backup): array
    {
        $manifest = json_decode((string) @file_get_contents($backup.'/manifest.json'), true);
        if (! is_array($manifest)) {
            throw new RuntimeException('That backup is incomplete and cannot be restored.');
        }
        foreach ($manifest['changed'] ?? [] as $logical) {
            $copy = $backup.'/files/'.$logical;
            if (is_file($copy)) {
                File::ensureDirectoryExists(dirname($this->target($logical)));
                @copy($copy, $this->target($logical));
            }
        }
        foreach ($manifest['added'] ?? [] as $logical) {
            @unlink($this->target($logical));
        }
        $this->resetOpcache();
        $manifest['restored_at'] = gmdate('c');
        file_put_contents($backup.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $manifest;
    }

    /** @return array{path: string, from: string, to: string, created_at: string, restored_at: ?string}|null The newest backup. */
    public function lastBackup(): ?array
    {
        $dirs = glob($this->backupRoot().'/*', GLOB_ONLYDIR) ?: [];
        rsort($dirs);
        foreach ($dirs as $dir) {
            $m = json_decode((string) @file_get_contents($dir.'/manifest.json'), true);
            if (is_array($m)) {
                return ['path' => $dir, 'from' => (string) $m['from'], 'to' => (string) $m['to'], 'created_at' => (string) $m['created_at'], 'restored_at' => $m['restored_at'] ?? null];
            }
        }

        return null;
    }

    /** Package path ("drinkwell/app/X.php", "public_html/build/a.css") to a site path ("app/X.php", "public/build/a.css"). */
    private function logical(string $name): ?string
    {
        if ($this->layout() !== 'public_html') {
            return $name;
        }
        if (str_starts_with($name, 'drinkwell/')) {
            $rest = substr($name, 10);

            return str_starts_with($rest, 'public/') ? null : $rest;
        }
        if (str_starts_with($name, 'public_html/')) {
            return 'public/'.substr($name, 12);
        }

        return null;
    }

    private function target(string $logical): string
    {
        return str_starts_with($logical, 'public/') ? $this->public.'/'.substr($logical, 7) : $this->base.'/'.$logical;
    }

    private function kept(string $logical): bool
    {
        foreach (self::KEEP as $pattern) {
            if (preg_match($pattern, $logical)) {
                return true;
            }
        }

        return false;
    }

    private function pruneBackups(int $keep): void
    {
        $dirs = glob($this->backupRoot().'/*', GLOB_ONLYDIR) ?: [];
        rsort($dirs);
        foreach (array_slice($dirs, $keep) as $old) {
            File::deleteDirectory($old);
        }
    }

    private function resetOpcache(): void
    {
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        clearstatcache();
    }

    /** A request to the GitHub API. $accept picks the answer: release information (JSON), or the file itself. */
    private function github(string $accept = 'application/vnd.github+json')
    {
        $request = Http::withHeaders(['User-Agent' => 'DRinkWeLL-updater', 'X-GitHub-Api-Version' => '2022-11-28', 'Accept' => $accept])
            ->timeout(30)->retry(2, 1500, throw: false);
        $token = (string) config('drinkwell.updates.token');

        return $token !== '' ? $request->withToken($token) : $request;
    }
}
