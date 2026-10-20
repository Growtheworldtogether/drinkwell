<?php

namespace App\Http\Controllers;

use App\Enums\SettlementStatus;
use App\Mail\DepositReportedAdmin;
use App\Models\MachineOwnership;
use App\Services\MailDispatcher;
use App\Services\PairingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * "I sent my deposit": records the date, an optional note and an optional receipt (photo or PDF), and tells the
     * administrators the first time. Sending again replaces the receipt. Nothing is charged; the deposit is off-platform.
     */
    public function report(Request $request, MailDispatcher $mail): RedirectResponse
    {
        $user = $request->user();
        $mine = MachineOwnership::query()->where('user_id', $user->id)->firstOrFail();
        $req = $user->pairingRequest;
        abort_unless($req && $mine->settlement_status === SettlementStatus::PendingSettlement, 404);

        $data = $request->validate([
            'proof' => ['nullable', 'file', 'max:5120', 'extensions:jpg,jpeg,png,webp,pdf'],
            'sent_on' => ['nullable', 'date', 'before_or_equal:today', 'after:2020-01-01'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [
            'proof.max' => 'The file is too big. Please choose one under 5 MB.',
            'proof.extensions' => 'Please choose a photo (JPG, PNG, WEBP) or a PDF.',
            'sent_on.before_or_equal' => 'The date cannot be in the future.',
        ], ['sent_on' => 'date']);

        $update = ['proof_sent_on' => $data['sent_on'] ?? now()->toDateString(), 'proof_note' => $data['note'] ?? null];

        if ($request->hasFile('proof')) {
            $file = $request->file('proof');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
            $path = $file->storeAs('proofs/'.$user->id, Str::uuid().'.'.$ext, 'local');
            if ($req->proof_path) {
                Storage::disk('local')->delete($req->proof_path);
            }
            $update += ['proof_path' => $path, 'proof_name' => Str::limit($file->getClientOriginalName(), 180, '')];
        }

        $first = ! $req->deposit_reported_at;
        $req->update($update + ($first ? ['deposit_reported_at' => now()] : []));

        if ($first) {
            $mail->sendToAdmins(fn () => new DepositReportedAdmin($user, $mine), 'funding');
        }

        return redirect()->route('onboard.status')->with('status', 'Thank you. We will let you know as soon as your co-partner confirms it.');
    }

    /** The member's own receipt, never public. */
    public function proof(Request $request): StreamedResponse
    {
        $req = $request->user()->pairingRequest;
        abort_unless($req?->proof_path && Storage::disk('local')->exists($req->proof_path), 404);

        return Storage::disk('local')->response($req->proof_path, $req->proof_name, ['Content-Disposition' => 'inline']);
    }
}
