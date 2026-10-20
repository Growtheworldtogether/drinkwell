<?php

namespace App\Http\Controllers;

use App\Models\VendingMachine;
use Illuminate\Http\JsonResponse;

/** Public map data for the landing page: where the machines are. No owners, no money. */
class PublicFeedController extends Controller
{
    public function machines(): JsonResponse
    {
        return response()->json([
            'sample' => true,
            'machines' => VendingMachine::query()->orderBy('id')->get()->map(fn (VendingMachine $m) => [
                'serial' => $m->serial_no,
                'facility' => $m->facility_name,
                'type' => $m->facility_type,
                'city' => $m->city,
                'tier' => $m->tier->value,
                'lat' => $m->latitude,
                'lng' => $m->longitude,
                'open' => $m->assignment_status->value === 'open',
            ])->values(),
        ]);
    }
}
