<?php

namespace App\Services;

use App\Models\VendingMachine;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Map tiles, kept on this server's disk. The first request for a tile fetches it once from the tile source with a
 * proper identifying User-Agent; every later request, from any browser, is served from disk. That makes the map fast,
 * keeps it working when the source is slow or down (stale tiles are served), and keeps browsers from hammering the
 * free public tile server. Street-level tiles are served only near a machine, and country views only inside the area
 * around all machines, so this is not an open proxy.
 */
class TileCache
{
    public const MIN_ZOOM = 3;

    /** Below this zoom (country and region views) a tile is allowed when it overlaps the area around all machines. */
    public const NEAR_ZOOM = 9;

    /** Margin around the machines' outer edges for the country views, in degrees. */
    private const AREA_MARGIN_DEG = 4.0;

    public const MAX_ZOOM = 17;

    /** Tiles within this many degrees of a depot hub are allowed. */
    private const RADIUS_DEG = 0.6;

    private const FRESH_DAYS = 30;

    /** Why the last fetch from the tile source failed, for the warm-up command to show. */
    private ?string $lastError = null;

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function path(int $z, int $x, int $y): string
    {
        return storage_path("app/tiles/{$z}/{$x}/{$y}.png");
    }

    public function allowed(int $z, int $x, int $y): bool
    {
        if ($z < self::MIN_ZOOM || $z > self::MAX_ZOOM) {
            return false;
        }

        $n = 2 ** $z;

        if ($x < 0 || $y < 0 || $x >= $n || $y >= $n) {
            return false;
        }

        if ($z < self::NEAR_ZOOM) {
            return $this->overlapsArea($z, $x, $y);
        }

        [$lat, $lng] = $this->centre($z, $x, $y);

        foreach ($this->hubs() as [$hubLat, $hubLng]) {
            if (abs($lat - $hubLat) <= self::RADIUS_DEG && abs($lng - $hubLng) <= self::RADIUS_DEG) {
                return true;
            }
        }

        return false;
    }

    /** The tile's PNG bytes, from disk or fetched once. Null when it cannot be had. */
    public function get(int $z, int $x, int $y): ?string
    {
        $file = $this->path($z, $x, $y);
        $fresh = is_file($file) && filemtime($file) > now()->subDays(self::FRESH_DAYS)->getTimestamp();

        if ($fresh) {
            return File::get($file);
        }

        $bytes = $this->fetch($z, $x, $y);

        if ($bytes !== null) {
            File::ensureDirectoryExists(dirname($file));
            // Write then rename, so a reader never sees half a file.
            $tmp = $file.'.'.bin2hex(random_bytes(4)).'.tmp';
            File::put($tmp, $bytes);
            rename($tmp, $file);

            return $bytes;
        }

        // The source is down or slow: an old tile is better than none.
        return is_file($file) ? File::get($file) : null;
    }

    private function fetch(int $z, int $x, int $y): ?string
    {
        $url = str_replace(['{z}', '{x}', '{y}'], [$z, $x, $y], (string) config('drinkwell.map.upstream'));

        try {
            $response = Http::withHeaders(['User-Agent' => $this->userAgent()])
                ->timeout(6)
                ->retry(1, 200, throw: false)
                ->get($url);
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();

            return null;
        }

        $isPng = str_starts_with((string) $response->header('Content-Type'), 'image/');

        if ($response->successful() && $isPng && $response->body() !== '') {
            return $response->body();
        }

        $this->lastError = 'The tile source answered HTTP '.$response->status().' for '.$url;

        return null;
    }

    private function userAgent(): string
    {
        $brand = app(BrandService::class);

        return preg_replace('/\s+/', '', $brand->name()).'/1.0 (school project; +'.config('app.url').'; '.$brand->supportEmail().')';
    }

    /** Country views: does the tile overlap the box around every machine, plus a margin? */
    private function overlapsArea(int $z, int $x, int $y): bool
    {
        $hubs = $this->hubs();
        if ($hubs === []) {
            return false;
        }
        $lats = array_column($hubs, 0);
        $lngs = array_column($hubs, 1);
        $n = 2 ** $z;
        $west = $x / $n * 360.0 - 180.0;
        $east = ($x + 1) / $n * 360.0 - 180.0;
        $north = rad2deg(atan(sinh(M_PI * (1 - 2 * $y / $n))));
        $south = rad2deg(atan(sinh(M_PI * (1 - 2 * ($y + 1) / $n))));

        return $east >= min($lngs) - self::AREA_MARGIN_DEG && $west <= max($lngs) + self::AREA_MARGIN_DEG
            && $north >= min($lats) - self::AREA_MARGIN_DEG && $south <= max($lats) + self::AREA_MARGIN_DEG;
    }

    /** @return array{0: float, 1: float} latitude and longitude of the tile's centre */
    public function centre(int $z, float $x, float $y): array
    {
        $n = 2 ** $z;
        $lng = ($x + 0.5) / $n * 360.0 - 180.0;
        $lat = rad2deg(atan(sinh(M_PI * (1 - 2 * ($y + 0.5) / $n))));

        return [$lat, $lng];
    }

    /** The tile column and row that hold a point. @return array{0: int, 1: int} */
    public static function tileFor(float $lat, float $lng, int $z): array
    {
        $n = 2 ** $z;
        $x = (int) floor(($lng + 180.0) / 360.0 * $n);
        $y = (int) floor((1 - log(tan(deg2rad($lat)) + 1 / cos(deg2rad($lat))) / M_PI) / 2 * $n);

        return [max(0, min($n - 1, $x)), max(0, min($n - 1, $y))];
    }

    /** @return list<array{0: float, 1: float}> */
    private function hubs(): array
    {
        return Cache::remember('tiles.hubs', 3600, fn () => VendingMachine::query()
            ->get(['latitude', 'longitude'])
            ->map(fn ($m) => [(float) $m->latitude, (float) $m->longitude])
            ->all());
    }
}
