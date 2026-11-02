<?php

namespace App\Console\Commands;

use App\Models\DailyDistribution;
use App\Models\MachineOwnership;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('drinkwell:fix-early-earnings {--force : Delete the rows. Without it, only report what would go}')]
#[Description('Remove payouts credited to a member for days before their deposit was confirmed')]
class FixEarlyEarningsCommand extends Command
{
    public function handle(): int
    {
        $tz = config('drinkwell.timezone');
        $rows = 0;
        $cents = 0;

        MachineOwnership::query()->whereNotNull('settled_at')->each(function (MachineOwnership $o) use ($tz, &$rows, &$cents) {
            $confirmed = $o->settled_at->copy()->setTimezone($tz)->toDateString();

            $bad = DailyDistribution::query()
                ->where('ownership_id', $o->id)
                ->whereDate('distribution_date', '<', $confirmed);

            $n = (clone $bad)->count();
            if ($n === 0) {
                return;
            }

            $rows += $n;
            $cents += (int) (clone $bad)->sum('payout_cents');
            $this->line("Ownership {$o->id} (user {$o->user_id}): {$n} early payout(s) before {$confirmed}");

            if ($this->option('force')) {
                $bad->delete();
            }
        });

        $this->info(sprintf('%s %d payout row(s), $%s in total.', $this->option('force') ? 'Removed' : 'Would remove', $rows, number_format($cents / 100, 2)));

        return self::SUCCESS;
    }
}
