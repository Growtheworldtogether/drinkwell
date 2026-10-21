<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WithdrawalStatus;
use App\Exceptions\WithdrawalException;
use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\AuditLogger;
use App\Services\SettingsRepository;
use App\Services\WithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The withdrawal queue. The administrator approves with a payment reference or declines with a reason. */
class WithdrawalsController extends Controller
{
    private const TABS = ['pending' => 'Waiting', 'approved' => 'Approved', 'rejected' => 'Declined', 'cancelled' => 'Cancelled'];

    public function __construct(private readonly WithdrawalService $svc, private readonly SettingsRepository $settings) {}

    public function index(Request $request): View
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'pending';
        $rows = Withdrawal::query()->with('user')->where('status', $tab)
            ->when($tab === 'pending', fn ($q) => $q->orderBy('confirmed_at'), fn ($q) => $q->orderByDesc('decided_at'))
            ->orderBy('id')->paginate(20)->withQueryString();

        $pending = Withdrawal::query()->where('status', WithdrawalStatus::Pending);

        return view('admin.withdrawals', [
            'tabs' => self::TABS,
            'tab' => $tab,
            'rows' => $rows,
            'counts' => Withdrawal::query()->whereIn('status', array_keys(self::TABS))->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
            'pendingCents' => (int) $pending->sum('amount_cents'),
            'balances' => $rows->getCollection()->mapWithKeys(fn (Withdrawal $w) => [$w->user_id => $this->svc->balance($w->user_id)]),
            'enabled' => $this->svc->enabled(),
            'target' => (int) config('drinkwell.withdrawal_review_days'),
        ]);
    }

    public function approve(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $data = $request->validate(['paid_reference' => ['required', 'string', 'max:64']]);

        try {
            $this->svc->approve($withdrawal, $request->user(), $data['paid_reference']);
        } catch (WithdrawalException $e) {
            return back()->withErrors(['paid_reference' => $e->getMessage()], 'w'.$withdrawal->id);
        }

        return redirect()->route('admin.withdrawals')->with('status', "Approved. {$withdrawal->user->name} has been emailed.");
    }

    public function reject(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $data = $request->validateWithBag('w'.$withdrawal->id, ['reason' => ['required', 'string', 'max:500']]);

        try {
            $this->svc->reject($withdrawal, $request->user(), $data['reason']);
        } catch (WithdrawalException $e) {
            return back()->withErrors(['reason' => $e->getMessage()], 'w'.$withdrawal->id);
        }

        return redirect()->route('admin.withdrawals')->with('status', "Declined. The amount went back to {$withdrawal->user->name}'s balance and they have been emailed.");
    }

    /** The master switch. Waiting requests are untouched; only new ones are blocked. */
    public function toggle(Request $request): RedirectResponse
    {
        $on = $request->boolean('enabled');
        $this->settings->set('withdrawals.enabled', $on, $request->user());
        AuditLogger::log($request->user(), 'withdrawals.toggle', null, null, ['enabled' => $on]);

        return back()->with('status', $on ? 'Withdrawals are on.' : 'Withdrawals are off. Members cannot ask for new ones.');
    }
}
