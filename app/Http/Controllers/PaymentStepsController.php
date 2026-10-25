<?php

namespace App\Http\Controllers;

use App\Models\MachineOwnership;
use App\Models\PairingRequest;
use App\Services\SettingsRepository;
use App\Support\DepositStyle;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Deposit style 2: three screens after pairing. 1, the co-partner match; 2, the admin's payment instruction, which
 * the member ticks as read; 3, a ready message to send the co-partner. Screen 3 opens only after the tick, and the
 * tick is asked for again every time the member goes through the screens.
 */
class PaymentStepsController extends Controller
{
    private const READ_KEY = 'payment_steps_read';

    public function show(Request $request, int $step): View|RedirectResponse
    {
        $data = $this->memberData($request);
        if ($data === null) {
            return redirect()->route('onboard.status');
        }
        if ($step === 1) {
            $request->session()->forget(self::READ_KEY);
        }
        if ($step === 3 && ! $request->session()->get(self::READ_KEY)) {
            return redirect()->route('onboard.steps', 2);
        }

        return view('onboard.steps', $data + [
            'step' => $step,
            'preview' => false,
            'urls' => [
                'step' => fn (int $n) => route('onboard.steps', $n),
                'read' => route('onboard.steps.read'),
                'done' => route('onboard.status'),
            ],
        ]);
    }

    public function read(Request $request): RedirectResponse
    {
        $request->validate(['read' => ['accepted']], ['read.accepted' => 'Please tick "I have read this" to go on.']);
        $data = $this->memberData($request);
        if ($data === null) {
            return redirect()->route('onboard.status');
        }
        $request->session()->put(self::READ_KEY, true);
        $data['req']->forceFill(['instructions_read_at' => now()])->save();

        return redirect()->route('onboard.steps', 3);
    }

    /** Admin, Settings, Deposit details, "Preview as a member": the same screens with a sample member. */
    public function preview(Request $request, int $step): View|RedirectResponse
    {
        if ($step === 3 && ! $request->session()->get(self::READ_KEY.'_preview')) {
            return redirect()->route('admin.settings.deposit.preview', 2);
        }
        if ($step === 1) {
            $request->session()->forget(self::READ_KEY.'_preview');
        }

        return view('onboard.steps', $this->sampleData() + [
            'step' => $step,
            'preview' => true,
            'urls' => [
                'step' => fn (int $n) => route('admin.settings.deposit.preview', $n),
                'read' => route('admin.settings.deposit.preview.read'),
                'done' => route('admin.settings.deposit'),
            ],
        ]);
    }

    public function previewRead(Request $request): RedirectResponse
    {
        $request->validate(['read' => ['accepted']], ['read.accepted' => 'Please tick "I have read this" to go on.']);
        $request->session()->put(self::READ_KEY.'_preview', true);

        return redirect()->route('admin.settings.deposit.preview', 3);
    }

    /** @return array<string, mixed>|null Null when the member is not paired, or style 2 is off. */
    private function memberData(Request $request): ?array
    {
        $user = $request->user();
        $req = $user->pairingRequest;
        $mine = MachineOwnership::query()->with('machine')->where('user_id', $user->id)->first();
        if (! DepositStyle::isContact() || ! $req || $req->status !== PairingRequest::PAIRED || ! $mine) {
            return null;
        }

        return $this->screenData(DepositStyle::wordsFor($user, $req, $mine), [
            'name' => (string) $req->partner_name,
            'location' => (string) $req->partner_city,
            'email' => (string) $req->partner_email,
        ]) + ['req' => $req];
    }

    /** @return array<string, mixed> */
    private function sampleData(): array
    {
        $d = Admin\SignupSettingsController::depositDefaults();
        $partner = ['name' => $d['partner_name'] ?: 'Margaret Whitfield', 'location' => $d['partner_city'] ?: 'Toronto, ON', 'email' => $d['partner_email'] ?: 'partner@example.com'];
        $words = DepositStyle::words([
            'member_name' => 'Helen Brooks',
            'partner_name' => $partner['name'],
            'partner_location' => $partner['location'],
            'partner_email' => $partner['email'],
            'amount' => Money::format(\App\Enums\Tier::Commercial->stakeCents()),
            'option' => \App\Enums\Tier::Commercial->label(),
            'machine' => 'DW-C-24159',
            'machine_location' => 'Northwestern Memorial Hospital, Chicago, IL',
            'reference' => 'DW-319164-1464',
        ]);

        return $this->screenData($words, $partner);
    }

    /** @param  array<string, string>  $words  @param  array{name: string, location: string, email: string}  $partner */
    private function screenData(array $words, array $partner): array
    {
        $texts = DepositStyle::texts(app(SettingsRepository::class));

        return [
            'partner' => $partner,
            'instruction' => DepositStyle::format(DepositStyle::fill($texts['instruction'], $words)),
            'subject' => DepositStyle::fill($texts['subject'], $words),
            'body' => DepositStyle::fill($texts['body'], $words),
        ];
    }
}
