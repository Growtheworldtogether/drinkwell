<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use PDOException;

/**
 * Typed access to the settings table (SPEC 4.1, 9.4).
 *
 * Reads go through one cached array. Every set() clears the cache, and writes an
 * audit row when an administrator is making the change.
 */
class SettingsRepository
{
    public const CACHE_KEY = 'settings.all';

    /** Cleared with the settings cache, because the brand array is built from it (SPEC 11.6). */
    public const BRAND_CACHE_KEY = 'brand';

    private bool $degraded = false;

    /** @return array<string, mixed> */
    public function all(): array
    {
        $this->degraded = false;

        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()
                ->get()
                ->mapWithKeys(fn (Setting $setting) => [$setting->key => $setting->value])
                ->all());
        } catch (QueryException|PDOException) {
            // Before the first migration there is no table. Fall back to defaults and cache nothing.
            $this->degraded = true;

            return [];
        }
    }

    /** True when the last read could not reach the settings table, so callers must not cache what they built. */
    public function isDegraded(): bool
    {
        return $this->degraded;
    }

    /** Stored value, else the configured default, else $default. */
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        return $this->defaults()[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key);

        return is_scalar($value) ? (string) $value : $default;
    }

    public function nullableString(string $key): ?string
    {
        $value = $this->get($key);

        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->get($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /** @return array<mixed> */
    public function array(string $key): array
    {
        $value = $this->get($key);

        return is_array($value) ? $value : [];
    }

    /**
     * Store a value. When $admin is null the signed-in user is used, and the change is
     * audit-logged if that user is an administrator.
     */
    public function set(string $key, mixed $value, ?User $admin = null): void
    {
        $admin ??= Auth::user();
        $admin = $admin?->role === Role::Admin ? $admin : null;

        $before = $this->get($key);

        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'updated_by' => $admin?->id],
        );

        $this->flush();

        if ($admin) {
            AuditLogger::log($admin, 'setting_updated', null, ['key' => $key, 'value' => $before], ['key' => $key, 'value' => $value]);
        }
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::BRAND_CACHE_KEY);
    }

    /** @return array<string, mixed> */
    private function defaults(): array
    {
        $brand = config('drinkwell.brand');

        return config('drinkwell.settings') + [
            'brand.name' => $brand['name'],
            'brand.tagline' => $brand['tagline'],
            'brand.logo_light_path' => $brand['logo_light_path'],
            'brand.logo_dark_path' => $brand['logo_dark_path'],
            'brand.favicon_path' => $brand['favicon_path'],
            'brand.updated_at' => $brand['updated_at'],
            'support_email' => $brand['support_email'],
        ];
    }
}
