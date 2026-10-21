<?php

use App\Models\VendingMachine;
use Database\Seeders\MachineSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

/**
 * Adds the twelve machines in eight new cities to a database that already has machines. A fresh install gets them
 * from the normal seeding instead, so nothing happens here when the machines table is empty (for example in tests).
 */
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
