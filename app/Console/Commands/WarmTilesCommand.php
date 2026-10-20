<?php

namespace App\Console\Commands;

use App\Models\VendingMachine;
use App\Services\TileCache;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('drinkwell:warm-tiles {--city= : Only this city}')]
#[Description('Fetch the map tiles around each machine facility once, so the first visitor sees the map instantly')]
class WarmTilesCommand extends Command
{
    public function handle(TileCache $tiles): int
    {
        $count = 0;

        VendingMachine::query()->when($this->option('city'), fn ($q, $c) => $q->where('city', $c))->get()->each(function (VendingMachine $d) use ($tiles, &$count) {
            // Zoom 12 to 15, the hub tile and its eight neighbours: the views the maps open on. About 36 tiles a machine.
            foreach ([12, 13, 14, 15] as $z) {
                [$cx, $cy] = TileCache::tileFor((float) $d->latitude, (float) $d->longitude, $z);

                foreach ([-1, 0, 1] as $dx) {
                    foreach ([-1, 0, 1] as $dy) {
                        if ($tiles->allowed($z, $cx + $dx, $cy + $dy) && $tiles->get($z, $cx + $dx, $cy + $dy) !== null) {
                            $count++;
                        }

                        usleep(150_000); // be polite to the tile source
                    }
                }
            }

            $this->line("{$d->facility_name}: done");
        });

        if ($count === 0) {
            $this->components->error('No tile could be fetched, so the maps will be blank.');
            $this->line('Reason: '.($tiles->lastError() ?? 'no machine exists.'));
            $this->line('A "cURL error 60" or "SSL certificate" message means PHP on this computer has no certificate bundle: set curl.cainfo and openssl.cafile in php.ini to a cacert.pem file (from curl.se/docs/caextract.html). An HTTP 403 means the tile source refused the request.');

            return self::FAILURE;
        }

        $this->components->info("{$count} tiles are on disk.");

        return self::SUCCESS;
    }
}
