<?php

namespace App\Services;

use App\Domain\DailyMath;
use App\Domain\DepletionCurve;
use App\Enums\HardwareState;
use App\Enums\SettlementStatus;
use App\Exceptions\SimulationRefused;
use App\Models\DailyDistribution;
use App\Models\MachineDay;
use App\Models\TelemetryLog;
use App\Models\VendingMachine;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Runs the machines. Every state change happens inside one transaction that first locks the machine row, so two
 * browser tabs, the scheduler and a toolbar click cannot interleave. Settling a day is idempotent: a second call for
 * the same machine and date finds the day already written (and the unique keys would refuse a duplicate anyway).
 *
 * The stock column is the truth. The curve sets a ceiling on what is left at a given minute; a sales burst can pull
 * the stock below that ceiling, never above it. At minute 1439 the ceiling is zero.
 */
class MachineEngine
{
    public const LOW_STOCK_RATIO = 0.15;

    /** Bring the machine's clock up to now, settling and rolling any day that has ended. */
    public function sync(VendingMachine|int $machine, ?CarbonInterface $now = null): VendingMachine
    {
        return $this->locked($machine, function (VendingMachine $m) use ($now) {
            $this->advance($m, $now ?? now());

            return $m;
        });
    }

    /** Fast-Forward 24 Hours: sell out and settle the current day, open the next one, restock. */
    public function fastForward(VendingMachine|int $machine): VendingMachine
    {
        return $this->locked($machine, function (VendingMachine $m) {
            $this->advance($m, now());
            $date = $m->operating_date->toDateString();
            $this->settleDay($m);
            $this->log($m, 'fast_forward', "Fast-forward: {$date} closed and settled.");
            $this->rollOver($m, 0);

            return $m;
        });
    }

    /** Trigger Sales Burst: pull forward up to the configured number of sales (default 25). */
    public function salesBurst(VendingMachine|int $machine): VendingMachine
    {
        return $this->locked($machine, function (VendingMachine $m) {
            $this->advance($m, now());

            if ($m->stock === 0) {
                throw new SimulationRefused('This machine is already sold out.');
            }

            $units = min((int) config('drinkwell.sales_burst_units'), $m->stock);
            $m->stock -= $units;
            $this->refreshState($m);
            $m->save();
            $this->log($m, 'sales_burst', "Sales burst: {$units} units sold at once.", ['units' => $units]);

            return $m;
        });
    }

    /** Driver Restock: only when sold out early. Brings the stock back to what the day's curve still has to sell. */
    public function driverRestock(VendingMachine|int $machine): VendingMachine
    {
        return $this->locked($machine, function (VendingMachine $m) {
            $this->advance($m, now());

            if ($m->stock > 0) {
                throw new SimulationRefused('The machine still has stock. A restock is only sent to a sold-out machine.');
            }

            $planned = $m->capacity - DepletionCurve::soldBy($m->capacity, $m->day_minute);

            if ($planned <= 0) {
                throw new SimulationRefused('The selling day is over. The next day starts with a full restock.');
            }

            $m->stock = $planned;
            $this->refreshState($m);
            $m->save();
            $this->log($m, 'driver_restock', "Driver restock: {$planned} units loaded.", ['units' => $planned]);

            return $m;
        });
    }

    /**
     * Write the machine day and its payouts for the machine's current operating date. Returns null when the day was
     * already settled. Must run inside a transaction that holds the machine lock.
     */
    public function settleDay(VendingMachine $m): ?MachineDay
    {
        $date = $m->operating_date->toDateString();

        if (MachineDay::query()->where('machine_id', $m->id)->whereDate('operating_date', $date)->exists()) {
            return null;
        }

        $math = DailyMath::day($m->tier, $m->serial_no, $date);

        $day = MachineDay::create([
            'machine_id' => $m->id,
            'operating_date' => $date,
            'units_sold' => $m->capacity,
            'gross_cents' => $math['gross'],
            'fee_cents' => $math['fee'],
            'pool_cents' => $math['pool'],
        ]);

        $owners = $m->ownerships()
            ->where('settlement_status', SettlementStatus::Settled)
            ->whereDate('earning_from', '<=', $date)
            ->orderBy('slot_no')
            ->get();

        foreach ($owners as $o) {
            DailyDistribution::create([
                'machine_day_id' => $day->id,
                'machine_id' => $m->id,
                'user_id' => $o->user_id,
                'ownership_id' => $o->id,
                'distribution_date' => $date,
                'payout_cents' => $math['share'],
                'ach_ref' => sprintf('ACH-%s-M%04d-U%05d', str_replace('-', '', $date), $m->id, $o->user_id),
            ]);
        }

        $this->log($m, 'settlement', sprintf('Day %s settled: %d units, %d owner payout(s).', $date, $m->capacity, $owners->count()), [
            'gross_cents' => $math['gross'], 'fee_cents' => $math['fee'], 'share_cents' => $math['share'],
        ]);

        return $day;
    }

    /** Move to the next operating date at the given minute, restocked. */
    private function rollOver(VendingMachine $m, int $minute): void
    {
        $m->operating_date = $m->operating_date->copy()->addDay();
        $m->day_minute = $minute;
        $m->clock_synced_at = now();
        $m->stock = $m->capacity - DepletionCurve::soldBy($m->capacity, $minute);
        $this->refreshState($m);
        $m->save();
        $this->log($m, 'driver_restock', "Nightly restock: {$m->stock} units loaded for {$m->operating_date->toDateString()}.", ['units' => $m->stock]);
    }

    private function advance(VendingMachine $m, CarbonInterface $now): void
    {
        $speed = max(0.01, (float) config('drinkwell.clock_speed'));
        $from = $m->clock_synced_at ?? $now;
        $simMinutes = (int) floor(max(0, $from->diffInSeconds($now, false)) / 60 * $speed);

        if ($simMinutes === 0) {
            return;
        }

        // Keep the fraction of a minute that was not used.
        $m->clock_synced_at = $from->copy()->addSeconds((int) round($simMinutes * 60 / $speed));
        $minute = $m->day_minute + $simMinutes;

        $guard = 0;
        while ($minute >= 1440 && $guard++ < 400) {
            $this->settleDay($m);
            $minute -= 1440;
            $m->operating_date = $m->operating_date->copy()->addDay();
            $m->day_minute = $minute;
            $m->stock = $m->capacity;
            $this->log($m, 'driver_restock', "Nightly restock: {$m->capacity} units loaded.", ['units' => $m->capacity]);
        }

        $m->day_minute = min($minute, 1439);
        $ceiling = $m->capacity - DepletionCurve::soldBy($m->capacity, $m->day_minute);
        $m->stock = min($m->stock, $ceiling);
        $this->refreshState($m);
        $m->save();
    }

    private function refreshState(VendingMachine $m): void
    {
        $new = match (true) {
            $m->stock === 0 => HardwareState::SoldOut,
            $m->stock <= (int) ceil($m->capacity * self::LOW_STOCK_RATIO) => HardwareState::LowStock,
            default => HardwareState::Online,
        };

        if ($new !== $m->hardware_state) {
            $old = $m->hardware_state;
            $m->hardware_state = $new;
            $this->log($m, 'state_change', "State {$old?->label()} to {$new->label()}.");
        }
    }

    /** @param  array<string, mixed>|null  $context */
    public function log(VendingMachine $m, string $event, string $message, ?array $context = null): void
    {
        TelemetryLog::create([
            'machine_id' => $m->id,
            'event' => $event,
            'message' => Str::limit($message, 190, ''),
            'context' => $context,
            'logged_at' => now(),
        ]);
    }

    /** @template T @param  callable(VendingMachine): T  $fn @return T */
    private function locked(VendingMachine|int $machine, callable $fn): mixed
    {
        $id = $machine instanceof VendingMachine ? $machine->id : $machine;

        return DB::transaction(function () use ($id, $fn) {
            $m = VendingMachine::query()->whereKey($id)->lockForUpdate()->firstOrFail();

            return $fn($m);
        });
    }
}
