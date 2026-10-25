<?php

namespace App\Services;

use App\Domain\DailyMath;
use App\Models\DailyDistribution;
use App\Models\MachineDay;
use App\Models\MachineOwnership;
use App\Models\VendingMachine;
use Illuminate\Support\Facades\DB;

/** Read-only consistency checks. Each returns a list of problems, empty when it passes. */
class Invariants
{
    public const KEY = 'drinkwell.integrity';

    public const DESCRIPTIONS = [
        'day_math' => 'Every settled day has pool = gross - fee, an even pool, and units sold equal to the machine capacity.',
        'payout_share' => 'Every payout equals half of its day pool.',
        'payout_band' => 'Every payout sits inside the tier band.',
        'no_double_pay' => 'No owner is paid twice for the same machine and date.',
        'stock_range' => 'Stock never exceeds capacity.',
        'slots' => 'Each machine has at most two owners and each owner holds one slot.',
        'min_age' => 'Every co-partner profile meets the minimum age at sign-up.',
    ];

    /** @return array<string, list<string>> */
    public function run(): array
    {
        return [
            'day_math' => $this->dayMath(),
            'payout_share' => $this->payoutShare(),
            'payout_band' => $this->payoutBand(),
            'no_double_pay' => $this->noDoublePay(),
            'stock_range' => $this->stockRange(),
            'slots' => $this->slots(),
            'min_age' => $this->minAge(),
        ];
    }

    private function dayMath(): array
    {
        $out = [];
        foreach (MachineDay::query()->with('machine')->cursor() as $d) {
            if ($d->pool_cents !== $d->gross_cents - $d->fee_cents || $d->pool_cents % 2 !== 0 || $d->units_sold !== $d->machine->capacity) {
                $out[] = "Day {$d->id} ({$d->machine->serial_no} {$d->operating_date->toDateString()}) breaks the day rules.";
            }
        }

        return $out;
    }

    private function payoutShare(): array
    {
        $out = [];
        foreach (DailyDistribution::query()->with('day')->cursor() as $p) {
            if ($p->payout_cents * 2 !== $p->day->pool_cents) {
                $out[] = "Payout {$p->id} is not half of its day pool.";
            }
        }

        return $out;
    }

    private function payoutBand(): array
    {
        $out = [];
        foreach (DailyDistribution::query()->with('machine')->cursor() as $p) {
            [$lo, $hi] = DailyMath::band($p->machine->tier);
            if ($p->payout_cents < $lo || $p->payout_cents > $hi) {
                $out[] = "Payout {$p->id} is outside {$lo} to {$hi} cents.";
            }
        }

        return $out;
    }

    private function noDoublePay(): array
    {
        return DailyDistribution::query()
            ->select('machine_id', 'user_id', 'distribution_date', DB::raw('count(*) as n'))
            ->groupBy('machine_id', 'user_id', 'distribution_date')
            ->havingRaw('count(*) > 1')
            ->get()
            ->map(fn ($r) => "Machine {$r->machine_id}, user {$r->user_id}, {$r->distribution_date}: {$r->n} payouts.")
            ->all();
    }

    private function stockRange(): array
    {
        return VendingMachine::query()->whereColumn('stock', '>', 'capacity')->pluck('serial_no')->map(fn ($s) => "{$s} holds more than its capacity.")->all();
    }

    private function slots(): array
    {
        $out = [];
        $rows = MachineOwnership::query()->select('machine_id', DB::raw('count(*) as n'), DB::raw('count(distinct user_id) as u'), DB::raw('count(distinct slot_no) as s'))->groupBy('machine_id')->get();
        foreach ($rows as $r) {
            if ($r->n > 2 || $r->u !== $r->n || $r->s !== $r->n) {
                $out[] = "Machine {$r->machine_id} has a bad slot layout.";
            }
        }

        return $out;
    }

    private function minAge(): array
    {
        return \App\Models\PartnerProfile::query()->where('age_at_signup', '<', (int) config('drinkwell.min_age'))->pluck('user_id')->map(fn ($id) => "User {$id} is under the minimum age.")->all();
    }
}
