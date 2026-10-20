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
        $mine = MachineOwnership::query()->with('machine')->where('user_id', $request->user()->id)->first();

        if (! $mine) {
            return redirect()->route('onboard');
        }

        return view('onboard.status', [
            'mine' => $mine,
            'machine' => $mine->machine,
            'partner' => $this->pairing->partnerOf($mine),
            'reference' => PairingService::reference($mine),
            'reported' => session('reported', false),
        ]);
    }

    /** "I sent my deposit": tells the administrators. Nothing is charged; the deposit is off-platform. */
    public function report(Request $request, MailDispatcher $mail): RedirectResponse
    {
        $mine = MachineOwnership::query()->where('user_id', $request->user()->id)->firstOrFail();

        if ($mine->settlement_status === SettlementStatus::PendingSettlement) {
            $mail->sendToAdmins(fn () => new DepositReportedAdmin($request->user(), $mine), 'funding');
        }

        return redirect()->route('onboard.status')->with('reported', true)->with('status', 'Thanks. An administrator will confirm your deposit.');
    }

    /** Demo shortcut so a classroom run needs no second person: settle the deposit now. */
    public function demoConfirm(Request $request): RedirectResponse
    {
        $mine = MachineOwnership::query()->where('user_id', $request->user()->id)->firstOrFail();
        $this->pairing->settle($mine);

        return redirect()->route('hub.dashboard')->with('status', 'Deposit confirmed (demo). Payouts start tomorrow.');
    }
}
