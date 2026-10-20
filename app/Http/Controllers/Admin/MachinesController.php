<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssignmentStatus;
use App\Enums\SettlementStatus;
use App\Http\Controllers\Controller;
use App\Models\DailyDistribution;
use App\Models\MachineOwnership;
use App\Models\MailLog;
use App\Models\User;
use App\Models\VendingMachine;
use App\Services\AuditLogger;
use App\Services\Invariants;
use App\Services\MachineView;
use App\Services\PairingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MachinesController extends Controller
{
    public function overview(): View
    {
        $last = cache(Invariants::KEY);

        return view('admin.overview', [
            'waiting' => \App\Models\PairingRequest::query()->where('status', 'finding')->count(),
            'machines' => VendingMachine::query()->count(),
            'open' => VendingMachine::query()->where('assignment_status', AssignmentStatus::Open)->count(),
            'pending' => MachineOwnership::query()->with('user', 'machine')->where('settlement_status', SettlementStatus::PendingSettlement)->latest('id')->limit(10)->get(),
            'paid_cents' => (int) DailyDistribution::query()->sum('payout_cents'),
            'partners' => User::query()->partners()->count(),
            'last_check' => $last,
            'mailer' => config('mail.default'),
            'simulated_mail' => MailLog::query()->where('status', 'simulated')->count(),
        ]);
    }

    public function index(): View
    {
        return view('admin.machines', [
            'machines' => VendingMachine::query()->orderBy('id')->get(),
        ]);
    }

    public function show(VendingMachine $machine, MachineView $view): View
    {
        return view('admin.machine', [
            'machine' => $machine,
            'snap' => $view->snapshot($machine),
            'owners' => $machine->ownerships()->with('user.profile')->orderBy('slot_no')->get(),
            'days' => $machine->days()->orderByDesc('operating_date')->limit(10)->get(),
        ]);
    }

    public function confirmDeposit(MachineOwnership $ownership, PairingService $pairing): RedirectResponse
    {
        $before = ['settlement_status' => $ownership->settlement_status->value];
        $pairing->settle($ownership);
        $ownership->refresh();
        AuditLogger::log(auth()->user(), 'ownership.confirm_deposit', $ownership, $before, ['settlement_status' => $ownership->settlement_status->value]);

        return back()->with('status', 'Deposit confirmed. Payouts start the next day.');
    }
}
