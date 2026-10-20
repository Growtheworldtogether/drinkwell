<?php

namespace App\Support;

/**
 * Immutable brand values shared with views as $brand (SPEC 11.6).
 */
final readonly class Brand
{
    public function __construct(
        public string $name,
        public ?string $tagline,
        public string $supportEmail,
        public ?string $logoLightUrl,
        public ?string $logoDarkUrl,
        public string $version,
    ) {}

    public function logoUrl(string $variant): ?string
    {
        return $variant === 'dark' ? $this->logoDarkUrl : $this->logoLightUrl;
    }

    public function iconUrl(int $size): string
    {
        return url('/brand/icon/'.$size).'?v='.$this->version;
    }
}
