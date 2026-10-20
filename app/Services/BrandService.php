<?php

namespace App\Services;

use App\Support\Brand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * One brand per installation (SPEC 11.6). Reads the brand.* and support_email
 * settings through SettingsRepository and caches one array under `brand`.
 * SettingsRepository::flush() clears it on every save.
 */
class BrandService
{
    public function __construct(private SettingsRepository $settings) {}

    public function name(): string
    {
        return $this->values()['name'];
    }

    public function tagline(): ?string
    {
        return $this->values()['tagline'] ?: null;
    }

    public function supportEmail(): string
    {
        return $this->values()['support_email'];
    }

    public function logoUrl(string $variant): ?string
    {
        $path = $this->values()[$variant === 'dark' ? 'logo_dark_path' : 'logo_light_path'];

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public function faviconUrl(int $size = 32): string
    {
        return url('/brand/icon/'.$size).'?v='.$this->version();
    }

    public function version(): string
    {
        return (string) ($this->values()['updated_at'] ?? '1');
    }

    public function brand(): Brand
    {
        return new Brand(
            name: $this->name(),
            tagline: $this->tagline(),
            supportEmail: $this->supportEmail(),
            logoLightUrl: $this->logoUrl('light'),
            logoDarkUrl: $this->logoUrl('dark'),
            version: $this->version(),
        );
    }

    /** Clear the cached brand array. Settings saves and resets call this through SettingsRepository::flush(). */
    public function flush(): void
    {
        $this->settings->flush();
    }

    /** @return array<string, mixed> */
    private function values(): array
    {
        $cached = Cache::get(SettingsRepository::BRAND_CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        $values = $this->build();

        if (! $this->settings->isDegraded()) {
            Cache::forever(SettingsRepository::BRAND_CACHE_KEY, $values);
        }

        return $values;
    }

    /** @return array<string, mixed> */
    private function build(): array
    {
        $name = trim($this->settings->string('brand.name'));
        $support = trim($this->settings->string('support_email'));
        $defaults = config('drinkwell.brand');

        return [
            'name' => $name !== '' ? $name : $defaults['name'],
            'tagline' => $this->settings->nullableString('brand.tagline'),
            'support_email' => $support !== '' ? $support : $defaults['support_email'],
            'logo_light_path' => $this->settings->nullableString('brand.logo_light_path'),
            'logo_dark_path' => $this->settings->nullableString('brand.logo_dark_path'),
            'favicon_path' => $this->settings->nullableString('brand.favicon_path'),
            'updated_at' => $this->settings->nullableString('brand.updated_at'),
        ];
    }
}
