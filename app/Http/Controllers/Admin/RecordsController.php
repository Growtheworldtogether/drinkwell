<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\MailLog;
use App\Models\User;
use App\Services\Invariants;
use App\Services\MailDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Read-only records: the audit log, the email log (with Resend) and the integrity report. */
class RecordsController extends Controller
{
    public function audit(Request $request): View
    {
        $admin = (int) $request->query('admin', 0);
        $action = trim((string) $request->query('action', ''));

        $rows = AdminAuditLog::query()->with('admin')
            ->when($admin > 0, fn ($q) => $q->where('admin_id', $admin))
            ->when($action !== '', fn ($q) => $q->where('action', $action))
            ->latest('id')->paginate(25)->withQueryString();

        return view('admin.audit', [
            'rows' => $rows,
            'admin_id' => $admin,
            'action' => $action,
            'admins' => User::query()->whereIn('id', AdminAuditLog::query()->select('admin_id')->distinct())->orderBy('name')->get(['id', 'name']),
            'actions' => AdminAuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
        ]);
    }

    public function emails(Request $request): View
    {
        $status = in_array($request->query('status'), ['sent', 'failed', 'simulated'], true) ? $request->query('status') : 'all';

        // SPEC 5.4: the last 200 sends.
        $latest = MailLog::query()->latest('id')->limit(200)->pluck('id');
        $query = MailLog::query()->with('user')->whereIn('id', $latest)->latest('id');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return view('admin.emails', [
            'rows' => $query->get(),
            'status' => $status,
            'failed' => MailLog::query()->whereIn('id', $latest)->where('status', 'failed')->count(),
            'mailer' => config('mail.default'),
        ]);
    }

    public function resend(MailLog $log, MailDispatcher $mail): RedirectResponse
    {
        if ($log->status !== 'failed' || $log->payload === null) {
            return back()->with('status', 'That message cannot be resent. Codes are never kept; ask the person to request a new one.');
        }

        return back()->with('status', $mail->resend($log) ? 'Sent again.' : 'Sending failed again. See the new row for the reason.');
    }

    public function integrity(): View
    {
        return view('admin.integrity', [
            'last' => cache(Invariants::KEY),
            'descriptions' => Invariants::DESCRIPTIONS,
            'results' => null,
        ]);
    }

    /** Runs every check now and shows each line, with the failures under it. */
    public function run(Invariants $invariants): View
    {
        $results = $invariants->run();
        $failed = array_keys(array_filter($results));
        cache()->forever(Invariants::KEY, ['at' => now()->timestamp, 'failed' => $failed]);

        return view('admin.integrity', [
            'last' => cache(Invariants::KEY),
            'descriptions' => Invariants::DESCRIPTIONS,
            'results' => $results,
        ]);
    }
}
