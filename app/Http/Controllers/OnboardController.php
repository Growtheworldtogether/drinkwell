<?php

namespace App\Http\Controllers;

use App\Enums\SettlementStatus;
use App\Mail\DepositReportedAdmin;
use App\Models\MachineOwnership;
use App\Services\MailDispatcher;
use App\Services\PairingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardController extends Controller
{
    public function __construct(private PairingService $pairing) {}

    public function status(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $req = $user->pairingRequest;
        $mine = MachineOwnership::query()->with('machine')->where('user_id', $user->id)->first();

        if (! $req && ! $mine) {
            return redirect()->route('onboard');
        }

        if (! $mine) {
            return view('onboard.status', ['req' => $req, 'mine' => null]);
        }

        return view('onboard.status', [
            'req' => $req,
            'mine' => $mine,
            'machine' => $mine->machine,
            'partner' => $this->pairing->partnerOf($mine),
            'reference' => $req?->reference ?: PairingService::reference($mine),
        ]);
    }

    /** "I sent my deposit": tells the administrators. Nothing is charged; the deposit is off-platform. */
    public function report(Request $request, MailDispatcher $mail): RedirectResponse
    {
        $mine = MachineOwnership::query()->where('user_id', $request->user()->id)->firstOrFail();
        $req = $request->user()->pairingRequest;

        if ($mine->settlement_status === SettlementStatus::PendingSettlement && $req && ! $req->deposit_reported_at) {
            $req->update(['deposit_reported_at' => now()]);
            $mail->sendToAdmins(fn () => new DepositReportedAdmin($request->user(), $mine), 'funding');
        }

        return redirect()->route('onboard.status')->with('status', 'Thanks. We will let you know as soon as your co-partner confirms it.');
    }
}
