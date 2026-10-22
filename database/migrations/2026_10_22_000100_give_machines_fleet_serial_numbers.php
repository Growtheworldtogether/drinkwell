<?php

use App\Enums\Tier;
use App\Models\VendingMachine;
use Illuminate\Database\Migrations\Migration;

/**
 * Replaces the starter serial numbers (DW-C-0001 and so on) with fleet numbers like DW-C-21486. Each machine keeps
 * its place in the order, so the same machine always gets the same new number. Settled days and payouts are stored
 * by machine id, not by serial number, so nothing else changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ([Tier::Commercial, Tier::Enterprise] as $tier) {
            VendingMachine::query()->where('tier', $tier)->orderBy('id')->get()->values()
                ->each(function (VendingMachine $m, int $i) use ($tier) {
                    if (preg_match('/^DW-[CE]-\d{4}$/', $m->serial_no)) {
                        $m->forceFill(['serial_no' => VendingMachine::serialFor($tier, $i + 1, $m->facility_name)])->save();
                    }
                });
        }
    }

    public function down(): void
    {
        // The old numbers are not restored; nothing depends on them.
    }
};
