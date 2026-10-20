<?php

namespace App\Services;

use App\Domain\DailyMath;
use App\Domain\DepletionCurve;
use App\Models\DailyDistribution;
use App\Models\MachineOwnership;
use App\Models\TelemetryLog;
use App\Models\User;
use App\Models\VendingMachine;
use Illuminate\Support\Carbon;

/** Builds what the dashboard shows about one machine and one owner. Read-only except for syncing the clock. */
class MachineView
{
    public function __construct(private MachineEngine $engine) {}

    public function machineFor(User $user): ?VendingMachine
    {
        $own = MachineOwnership::query()->where('user_id', $user->id)->first();

        return $own ? VendingMachine::query()->find($own->machine_id) : null;
    }

    /** @return array<string, mixed> */
    public function snapshot(VendingMachine $machine, ?User $viewer = null): array
    {
        $m = $this->engine->sync($machine);
        $cap = $m->capacity;
        $sold = $cap - $m->stock;
        $minute = $m->day_minute;
        $curve = DepletionCurve::soldBy($cap, $minute);

        $ticker = [];
        for ($u = $sold; $u >= max(1, $sold - 9); $u--) {
            $at = $u <= $curve ? DepletionCurve::minuteOfUnit($cap, $u) : $minute;
            $ticker[] = ['unit' => $u, 'time' => sprintf('%02d:%02d', intdiv($at, 60), $at % 60), 'cents' => (int) config('drinkwell.unit_price_cents')];
        }

        $share = DailyMath::day($m->tier, $m->serial_no, $m->operating_date->toDateString())['share'];

        $snap = [
            'serial' => $m->serial_no,
            'tier' => $m->tier->value,
            'tier_label' => $m->tier->label(),
            'facility' => $m->facility_name,
            'facility_type' => $m->facility_type,
            'city' => $m->city,
            'lat' => $m->latitude,
            'lng' => $m->longitude,
            'capacity' => $cap,
            'stock' => $m->stock,
            'sold_today' => $sold,
            'fill_pct' => $cap > 0 ? (int) round($m->stock / $cap * 100) : 0,
            'state' => $m->hardware_state->value,
            'state_label' => $m->hardware_state->label(),
            'assignment' => $m->assignment_status->value,
            'date' => $m->operating_date->toDateString(),
            'clock' => sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60),
            'temp_c' => round(3.5 + ((crc32($m->serial_no.':'.$minute) % 11) - 5) / 10, 1),
            'revenue_today_cents' => $sold * (int) config('drinkwell.unit_price_cents'),
            'accrued_today_cents' => intdiv($share * $sold, max(1, $cap)),
            'ticker' => $ticker,
            'logs' => TelemetryLog::query()->where('machine_id', $m->id)->latest('id')->limit(8)->get()
                ->map(fn ($l) => ['at' => Carbon::parse($l->logged_at)->setTimezone(config('drinkwell.timezone'))->format('H:i:s'), 'event' => $l->event, 'message' => $l->message])->all(),
            'can_restock' => $m->stock === 0 && $curve < $cap,
            'can_burst' => $m->stock > 0,
        ];

        if ($viewer) {
            $paid = (int) DailyDistribution::query()->where('user_id', $viewer->id)->sum('payout_cents');
            $own = MachineOwnership::query()->where('user_id', $viewer->id)->where('machine_id', $m->id)->first();
            $snap['paid_cents'] = $paid;
            $snap['stake_cents'] = (int) ($own?->capital_cents ?? 0);
            $snap['settlement'] = $own?->settlement_status->value;
            $snap['payback_pct'] = $snap['stake_cents'] > 0 ? min(100, (int) floor($paid / $snap['stake_cents'] * 100)) : 0;
        }

        return $snap;
    }
}
