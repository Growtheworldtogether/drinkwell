<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\NoMachineAvailable;
use App\Exceptions\SimulationRefused;
use App\Models\VendingMachine;
use App\Services\MachineEngine;
use App\Http\Controllers\Controller;
use App\Models\MachineOwnership;
use App\Models\PairingRequest;
use App\Services\AuditLogger;
use App\Services\PairingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** The administrator plays the co-partner: pairs waiting members and enters the payment details they will see. */
class PairingsController extends Controller
{
    public function index(): View
    {
        return view('admin.pairings', [
            'waiting' => PairingRequest::query()->with('user')->where('status', PairingRequest::FINDING)->oldest()->get(),
            'paired' => PairingRequest::query()->with('user')->where('status', PairingRequest::PAIRED)->latest('paired_at')->get(),
            'ownerships' => MachineOwnership::query()->get()->keyBy('user_id'),
            'machines' => VendingMachine::query()->orderBy('id')->get(['id', 'serial_no', 'facility_name']),
        ]);
    }

    public function assign(Request $request, PairingRequest $pairing, PairingService $svc): RedirectResponse
    {
        abort_unless($pairing->status === PairingRequest::FINDING, 404);
        $data = $this->validated($request, $pairing);

        try {
            $mine = $svc->assign($pairing, $data);
        } catch (NoMachineAvailable $e) {
            return back()->withErrors(['machine' => $e->getMessage()], 'p'.$pairing->id)->withInput(['_pid' => $pairing->id]);
        }

        AuditLogger::log($request->user(), 'pairing.assign', $pairing, ['status' => 'finding'], ['status' => 'paired', 'machine_id' => $mine->machine_id, 'partner_name' => $data['partner_name']]);

        return redirect()->route('admin.pairings')->with('status', "Paired {$pairing->user->name}. The email with the payment details is on its way.");
    }

    public function update(Request $request, PairingRequest $pairing, PairingService $svc): RedirectResponse
    {
        abort_unless($pairing->isPaired(), 404);
        $data = $this->validated($request, $pairing);
        $before = ['partner_name' => $pairing->partner_name, 'bank_name' => $pairing->bank_name, 'account_last4' => $pairing->account_last4, 'reference' => $pairing->reference];
        $svc->updateDetails($pairing, $data);
        AuditLogger::log($request->user(), 'pairing.update_details', $pairing, $before, ['partner_name' => $data['partner_name'], 'bank_name' => $data['bank_name'], 'account_last4' => substr($data['account_number'], -4)]);

        return redirect()->route('admin.pairings')->with('status', "Details updated. {$pairing->user->name} has been emailed the corrected version.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, PairingRequest $pairing): array
    {
        return $request->validateWithBag('p'.$pairing->id, [
            'partner_name' => ['required', 'string', 'max:120'],
            'partner_city' => ['nullable', 'string', 'max:80'],
            'account_holder' => ['required', 'string', 'max:120'],
            'bank_name' => ['required', 'string', 'max:120'],
            'routing_number' => ['nullable', 'string', 'max:20'],
            'account_number' => ['required', 'string', 'max:34'],
            'reference' => ['nullable', 'string', 'max:40'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /** A member's receipt, for administrators only. */
    public function proof(PairingRequest $pairing): StreamedResponse
    {
        abort_unless($pairing->proof_path && Storage::disk('local')->exists($pairing->proof_path), 404);

        return Storage::disk('local')->response($pairing->proof_path, $pairing->proof_name, ['Content-Disposition' => 'inline']);
    }

    /** Demo tools: run the clock, a sales burst or a driver restock on any machine. */
    public function demo(Request $request, MachineEngine $engine): RedirectResponse
    {
        $data = $request->validate(['machine_id' => ['required', 'integer', 'exists:vending_machines,id'], 'do' => ['required', 'in:advance,burst,restock']]);
        $machine = VendingMachine::query()->findOrFail($data['machine_id']);

        try {
            match ($data['do']) {
                'advance' => $engine->fastForward($machine),
                'burst' => $engine->salesBurst($machine),
                'restock' => $engine->driverRestock($machine),
            };
        } catch (SimulationRefused $e) {
            return back()->with('sim_error', $e->getMessage());
        }

        AuditLogger::log($request->user(), 'sim.'.$data['do'], $machine);

        return back()->with('status', 'Done: '.['advance' => 'fast-forwarded 24 hours', 'burst' => 'sales burst', 'restock' => 'driver restock'][$data['do']].' on '.$machine->serial_no.'.');
    }
}
