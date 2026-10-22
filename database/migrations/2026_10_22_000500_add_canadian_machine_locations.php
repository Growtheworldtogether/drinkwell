<?php

use App\Models\VendingMachine;
use Database\Seeders\MachineSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

/** Adds the three Canadian machines (Toronto twice, Vancouver) to a database that already has machines. */
return new class extends Migration
{
    public function up(): void
    {
        if (! VendingMachine::query()->exists()) {
            return;
        }

        (new MachineSeeder)->run();
        Cache::forget('tiles.hubs');
    }

    public function down(): void
    {
        // New machines may already be paired with members, so they are not removed automatically.
    }
};
