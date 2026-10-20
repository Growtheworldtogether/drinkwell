<?php

namespace App\Http\Controllers;

use App\Models\DailyDistribution;
use App\Services\MachineView;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HubController extends Controller
{
    public function __construct(private MachineView $view) {}

    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $machine = $this->view->machineFor($user);
        abort_if($machine === null, 404);

        $rows = DailyDistribution::query()->with('day')->where('user_id', $user->id)->orderByDesc('distribution_date')->limit(30)->get();

        return view('hub.dashboard', [
            'snap' => $this->view->snapshot($machine, $user),
            'machine' => $machine,
            'ledger' => $rows,
            'chart' => $rows->reverse()->values()->map(fn ($d) => ['date' => $d->distribution_date->format('M j'), 'cents' => $d->payout_cents]),
        ]);
    }

    public function telemetry(Request $request): JsonResponse
    {
        $machine = $this->view->machineFor($request->user());
        abort_if($machine === null, 404);

        return response()->json($this->view->snapshot($machine, $request->user()));
    }

    public function ledgerCsv(Request $request): StreamedResponse
    {
        $user = $request->user();
        $rows = DailyDistribution::query()->with('day', 'machine')->where('user_id', $user->id)->orderBy('distribution_date');

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['date', 'machine', 'units_sold', 'gross_usd', 'fee_usd', 'pool_usd', 'payout_usd', 'ach_ref']);
            foreach ($rows->cursor() as $d) {
                fputcsv($out, [
                    $d->distribution_date->toDateString(), $d->machine->serial_no, $d->day->units_sold,
                    Money::plain($d->day->gross_cents), Money::plain($d->day->fee_cents), Money::plain($d->day->pool_cents),
                    Money::plain($d->payout_cents), $d->ach_ref,
                ]);
            }
            fclose($out);
        }, 'drinkwell-ledger.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
