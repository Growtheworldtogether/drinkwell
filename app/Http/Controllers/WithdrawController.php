<?php

namespace App\Http\Controllers;

use App\Enums\WithdrawalStatus;
use App\Exceptions\MailSendFailed;
use App\Exceptions\OtpException;
use App\Exceptions\WithdrawalException;
use App\Models\Withdrawal;
use App\Services\OtpService;
use App\Services\WithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The member side of withdrawals: ask, confirm with the emailed code, see history, cancel a waiting request. */
class WithdrawController extends Controller
{
    public function __construct(private readonly WithdrawalService $svc) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $all = $user->withdrawals()->latest('id')->limit(15)->get();
        $awaiting = $all->first(fn (Withdrawal $w) => $w->status === WithdrawalStatus::AwaitingConfirmation
            && $w->created_at->gte(now()->subMinutes(OtpService::TTL_MINUTES)));
        [$local, $domain] = array_pad(explode('@', (string) $user->email, 2), 2, '');

        return view('hub.withdraw', [
            'earned' => $this->svc->earned($user),
            'available' => $this->svc->balance($user),
            'waiting' => (int) $user->withdrawals()->where('status', WithdrawalStatus::Pending)->sum('amount_cents'),
            'paidOut' => (int) $user->withdrawals()->where('status', WithdrawalStatus::Approved)->sum('amount_cents'),
            'min' => WithdrawalService::minCents(),
            'enabled' => $this->svc->enabled(),
            'account' => $user->payoutAccounts()->where('is_default', true)->first() ?? $user->payoutAccounts()->first(),
            'awaiting' => $awaiting,
            'history' => $all->reject(fn (Withdrawal $w) => $w->status === WithdrawalStatus::AwaitingConfirmation)->values(),
            'maskedEmail' => ($local !== '' ? substr($local, 0, 1) : '').'***@'.$domain,
            'reviewDays' => (int) config('drinkwell.withdrawal_review_days'),
        ]);
    }

    public function request(Request $request): RedirectResponse
    {
        $request->validate(['amount' => ['required', 'string', 'max:20']], ['amount.required' => 'Enter how much you want to withdraw.']);

        try {
            $this->svc->request($request->user(), WithdrawalService::parseDollars($request->string('amount')->toString()));
        } catch (WithdrawalException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        } catch (OtpException|MailSendFailed $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()->route('hub.withdraw')->with('status', 'We emailed you a six digit code. Enter it below to finish.');
    }

    public function confirm(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $this->own($request, $withdrawal);
        $request->validate(['code' => ['required', 'digits:6']], [
            'code.required' => 'Enter the six digit code from the email.',
            'code.digits' => 'The code is six digits. Check the email and try again.',
        ]);

        try {
            $this->svc->confirm($withdrawal, $request->string('code')->toString());
        } catch (OtpException|WithdrawalException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return redirect()->route('hub.withdraw')->with('status', 'Your withdrawal is in. We will review it within '.config('drinkwell.withdrawal_review_days').' business days.');
    }

    public function resend(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $this->own($request, $withdrawal);

        try {
            $this->svc->resend($withdrawal);
        } catch (OtpException|MailSendFailed|WithdrawalException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return back()->with('status', 'A new code is on its way.');
    }

    public function cancel(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $this->own($request, $withdrawal);

        try {
            if ($withdrawal->status === WithdrawalStatus::AwaitingConfirmation) {
                $withdrawal->delete();
            } else {
                $this->svc->cancel($withdrawal);
            }
        } catch (WithdrawalException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('hub.withdraw')->with('status', 'Request cancelled. The amount is back in your balance.');
    }

    private function own(Request $request, Withdrawal $withdrawal): void
    {
        abort_unless($withdrawal->user_id === $request->user()->id, 404);
    }
}
