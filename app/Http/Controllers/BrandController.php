<?php

namespace App\Http\Controllers;

use App\Services\BrandService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BrandController extends Controller
{
    private const ICON_SIZES = [16, 32, 180, 192, 512];

    public function __construct(private BrandService $brand) {}

    /** The uploaded favicon at this size, else the built-in default mark: a black square with a white inner square. */
    public function icon(int $size): Response
    {
        abort_unless(in_array($size, self::ICON_SIZES, true), 404);

        $uploaded = app(\App\Services\BrandAssets::class)->icon(app(\App\Services\SettingsRepository::class)->nullableString('brand.favicon_path'), $size);

        if ($uploaded !== null) {
            return response($uploaded, 200, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=86400']);
        }

        $image = imagecreatetruecolor($size, $size);
        $black = imagecolorallocate($image, 0, 0, 0);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, $size, $size, $black);
        $inset = (int) round($size * 0.3);
        imagefilledrectangle($image, $inset, $inset, $size - $inset - 1, $size - $inset - 1, $white);

        ob_start();
        imagepng($image);
        $png = ob_get_clean();

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function favicon(): Response
    {
        return $this->icon(32);
    }

    public function logo(string $variant): Response
    {
        abort_unless(in_array($variant, ['light', 'dark'], true), 404);
        $url = $this->brand->logoUrl($variant);
        abort_if($url === null, 404);

        return redirect($url);
    }

    public function manifest(): JsonResponse
    {
        return response()->json([
            'name' => $this->brand->name(),
            'short_name' => $this->brand->name(),
            'icons' => [
                ['src' => $this->brand->faviconUrl(192), 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => $this->brand->faviconUrl(512), 'sizes' => '512x512', 'type' => 'image/png'],
            ],
            'theme_color' => '#000000',
            'background_color' => '#FFFFFF',
            'display' => 'browser',
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }
}
