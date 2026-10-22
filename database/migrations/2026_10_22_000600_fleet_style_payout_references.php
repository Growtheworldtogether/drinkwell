<?php

use App\Models\DailyDistribution;
use App\Services\MachineEngine;
use Illuminate\Database\Migrations\Migration;

/** Rewrites payout references like ACH-20261001-M0001-U00002 to the fleet style, ACH-20261001-21430-18014. */
return new class extends Migration
{
    public function up(): void
    {
        DailyDistribution::query()->with('machine')->where('ach_ref', 'like', 'ACH-%-M%-U%')->chunkById(500, function ($rows) {
            foreach ($rows as $d) {
                $d->forceFill(['ach_ref' => MachineEngine::payoutReference($d->distribution_date->toDateString(), $d->machine->serial_no, $d->user_id)])->save();
            }
        });
    }

    public function down(): void
    {
        // The old references are not restored; nothing depends on them.
    }
};
