<?php

namespace App\Http\Controllers;

use App\Domain\DailyMath;
use App\Enums\Tier;
use App\Models\VendingMachine;
use Illuminate\View\View;

class PagesController extends Controller
{
    public function home(): View
    {
        return view('marketing.home', [
            'tiers' => $this->tiers(),
            'machines' => VendingMachine::query()->count(),
            'facilities' => VendingMachine::query()->orderBy('facility_name')->pluck('facility_name')->unique()->values()->all(),
        ]);
    }

    public function program(): View
    {
        return view('marketing.program', ['tiers' => $this->tiers()]);
    }

    public function terms(): View
    {
        return view('marketing.terms');
    }

    public function privacy(): View
    {
        return view('marketing.privacy');
    }

    /** Figures for the calculator and the tier cards, straight from config and the daily math. @return array<string, array<string, mixed>> */
    private function tiers(): array
    {
        $out = [];
        foreach (Tier::cases() as $t) {
            $nominal = DailyMath::day($t, 'NOMINAL', '2026-01-01');
            [$min, $max] = DailyMath::band($t);
            $out[$t->value] = [
                'label' => $t->label(),
                'capacity' => $t->capacity(),
                'machine_cost' => $t->machineCostCents(),
                'stake' => $t->stakeCents(),
                'gross' => $t->grossCents(),
                'fee' => $t->cfg()['fee_center_cents'],
                'pool' => $t->grossCents() - $t->cfg()['fee_center_cents'],
                'share' => intdiv($t->grossCents() - $t->cfg()['fee_center_cents'], 2),
                'band_min' => $min,
                'band_max' => $max,
                'payback' => round($t->stakeCents() / intdiv($t->grossCents() - $t->cfg()['fee_center_cents'], 2), 1),
            ];
        }

        return $out;
    }
}
