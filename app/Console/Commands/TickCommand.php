<?php

namespace App\Console\Commands;

use App\Models\VendingMachine;
use App\Services\MachineEngine;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('drinkwell:tick')]
#[Description('Bring every machine clock up to now: sell down stock, settle finished days and restock')]
class TickCommand extends Command
{
    public const KEY = 'drinkwell.tick_at';

    public function handle(MachineEngine $engine): int
    {
        $ok = 0;
        $failed = 0;

        foreach (VendingMachine::query()->orderBy('id')->pluck('id') as $id) {
            try {
                $engine->sync($id);
                $ok++;
            } catch (Throwable $e) {
                $failed++;
                report($e);
            }
        }

        cache()->forever(self::KEY, now()->timestamp);
        $this->components->info("Ticked {$ok} machines, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
