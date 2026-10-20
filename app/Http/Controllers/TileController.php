<?php

namespace App\Http\Controllers;

use App\Services\TileCache;
use Illuminate\Http\Response;

/** GET /map/tiles/{z}/{x}/{y}.png: one map tile, from this server's disk cache (see TileCache). */
class TileController extends Controller
{
    public function show(TileCache $tiles, int $z, int $x, int $y): Response
    {
        abort_unless($tiles->allowed($z, $x, $y), 404);

        $bytes = $tiles->get($z, $x, $y);
        abort_if($bytes === null, 404);

        return response($bytes, 200, [
            'Content-Type' => 'image/png',
            // A tile does not change. Browsers keep it for a month and never ask again.
            'Cache-Control' => 'public, max-age=2592000, immutable',
        ]);
    }
}
