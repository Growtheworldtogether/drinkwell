<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Brand image uploads (SPEC 11.6). Every upload is checked by its content, decoded and written again through GD, so no
 * metadata or payload survives and the stored file is always a clean PNG. SVG is refused. Names are random, never the original.
 */
class BrandAssets
{
    public const LOGO_MIN_WIDTH = 240;

    public const LOGO_MAX_KB = 1024;

    public const MAX_EDGE = 2000;

    public const FAVICON_MIN = 512;

    public const FAVICON_MAX_KB = 512;

    public const ICON_SIZES = [16, 32, 180, 192, 512];

    private const MIMES = ['image/png', 'image/webp', 'image/jpeg'];

    private const DISK = 'public';

    /** Store a logo and return its path on the public disk. @throws ValidationException keyed by $field */
    public function storeLogo(UploadedFile $file, string $field): string
    {
        $image = $this->decode($file, $field, self::MIMES, self::LOGO_MAX_KB, 'Logo');
        $width = imagesx($image);

        if ($width < self::LOGO_MIN_WIDTH) {
            throw ValidationException::withMessages([$field => "Logo is {$width} px wide. Use an image at least ".self::LOGO_MIN_WIDTH.' px wide.']);
        }

        $path = 'brand/'.Str::random(40).'.png';
        Storage::disk(self::DISK)->put($path, $this->png($this->fit($image, self::MAX_EDGE)));

        return $path;
    }

    /**
     * Store a favicon: a square PNG. Every icon size is derived and written next to a random token; the token is returned.
     *
     * @throws ValidationException keyed by $field
     */
    public function storeFavicon(UploadedFile $file, string $field): string
    {
        $image = $this->decode($file, $field, ['image/png'], self::FAVICON_MAX_KB, 'Favicon');
        [$w, $h] = [imagesx($image), imagesy($image)];

        if ($w !== $h) {
            throw ValidationException::withMessages([$field => "Favicon is {$w} by {$h} px. Use a square image."]);
        }
        if ($w < self::FAVICON_MIN) {
            throw ValidationException::withMessages([$field => "Favicon is {$w} px. Use a square image at least ".self::FAVICON_MIN.' px.']);
        }

        $token = 'brand/icons/'.Str::random(40);

        foreach (self::ICON_SIZES as $size) {
            $out = imagecreatetruecolor($size, $size);
            $this->transparent($out);
            imagecopyresampled($out, $image, 0, 0, 0, 0, $size, $size, $w, $h);
            Storage::disk(self::DISK)->put("{$token}-{$size}.png", $this->png($out));
        }

        return $token;
    }

    /** Remove a stored logo, or every derived icon for a favicon token. Missing files are fine. */
    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        if (str_starts_with($path, 'brand/icons/')) {
            foreach (self::ICON_SIZES as $size) {
                Storage::disk(self::DISK)->delete("{$path}-{$size}.png");
            }

            return;
        }

        if (str_starts_with($path, 'brand/')) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /** The derived icon for a favicon token, as PNG bytes, or null when it is missing. */
    public function icon(?string $token, int $size): ?string
    {
        if (blank($token) || ! in_array($size, self::ICON_SIZES, true)) {
            return null;
        }

        $file = "{$token}-{$size}.png";

        return Storage::disk(self::DISK)->exists($file) ? Storage::disk(self::DISK)->get($file) : null;
    }

    /** @param  list<string>  $allowed */
    private function decode(UploadedFile $file, string $field, array $allowed, int $maxKb, string $what): \GdImage
    {
        $bytes = (string) @file_get_contents($file->getRealPath());
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);

        if ($mime === 'image/svg+xml' || str_contains(strtolower(substr($bytes, 0, 1000)), '<svg') || strtolower($file->getClientOriginalExtension()) === 'svg') {
            throw ValidationException::withMessages([$field => 'SVG files are not accepted because they can carry scripts. Upload '.$this->accepted($allowed).'.']);
        }
        if (! in_array($mime, $allowed, true)) {
            throw ValidationException::withMessages([$field => "{$what} is not a supported image. Upload ".$this->accepted($allowed).'.']);
        }
        if (strlen($bytes) > $maxKb * 1024) {
            $size = number_format(strlen($bytes) / 1024, 0);
            throw ValidationException::withMessages([$field => "{$what} is {$size} KB. Use a file up to {$maxKb} KB."]);
        }

        $image = @imagecreatefromstring($bytes);
        if (! $image instanceof \GdImage) {
            throw ValidationException::withMessages([$field => "{$what} could not be read. Export it again as ".$this->accepted($allowed).'.']);
        }

        return $image;
    }

    /** @param  list<string>  $allowed */
    private function accepted(array $allowed): string
    {
        return $allowed === ['image/png'] ? 'a PNG' : 'a PNG, WebP or JPG';
    }

    private function fit(\GdImage $image, int $edge): \GdImage
    {
        [$w, $h] = [imagesx($image), imagesy($image)];
        $scale = min(1, $edge / max($w, $h));

        if ($scale >= 1) {
            return $image;
        }

        [$nw, $nh] = [max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale))];
        $out = imagecreatetruecolor($nw, $nh);
        $this->transparent($out);
        imagecopyresampled($out, $image, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $out;
    }

    private function transparent(\GdImage $image): void
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
    }

    private function png(\GdImage $image): string
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }
}
