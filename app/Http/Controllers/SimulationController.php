<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Exceptions\SimulationRefused;
use App\Models\MachineOwnership;
use App\Models\VendingMachine;
use App\Services\AuditLogger;
use App\Services\MachineEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** The demo toolbar's three commands. A partner may only drive their own machine; an administrator may drive any. */
class SimulationController extends Controller
{
    public function __construct(private MachineEngine $engine) {}

    public function advanceDay(Request $request, VendingMachine $machine): RedirectResponse
    {
        return $this->run($request, $machine, 'sim.advance_day', 'Fast-forwarded 24 hours. The day was closed and paid out.', fn () => $this->engine->fastForward($machine));
    }

    public function salesBurst(Request $request, VendingMachine $machine): RedirectResponse
    {
        return $this->run($request, $machine, 'sim.sales_burst', 'Sales burst: up to 25 drinks sold.', fn () => $this->engine->salesBurst($machine));
    }

    public function driverRestock(Request $request, VendingMachine $machine): RedirectResponse
    {
        return $this->run($request, $machine, 'sim.driver_restock', 'A driver restocked the machine.', fn () => $this->engine->driverRestock($machine));
    }

    private function run(Request $request, VendingMachine $machine, string $action, string $ok, callable $do): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== Role::Admin) {
            abort_unless(MachineOwnership::query()->where('machine_id', $machine->id)->where('user_id', $user->id)->exists(), 403);
        }

        try {
            $do();
        } catch (SimulationRefused $e) {
            return back()->with('sim_error', $e->getMessage());
        }

        if ($user->role === Role::Admin) {
            AuditLogger::log($user, $action, $machine);
        }

        return back()->with('status', $ok);
    }
}
