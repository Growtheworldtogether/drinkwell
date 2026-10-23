<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InviteCode;
use App\Services\AuditLogger;
use App\Services\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Settings for new members: the reference codes they sign up with, and the deposit details new pairings start from. */
class SignupSettingsController extends Controller
{
    public const DEPOSIT_KEY = 'deposit_defaults';

    /** On: a new member is paired on the spot with the default details, after a short search on screen. */
    public const AUTO_PAIR_KEY = 'auto_pair';

    /** The default details a pairing cannot go without. */
    public const REQUIRED_FOR_AUTO = ['partner_name', 'account_holder', 'bank_name', 'account_number'];

    public const DEPOSIT_FIELDS = ['partner_name', 'partner_city', 'account_holder', 'bank_name', 'routing_number', 'account_number', 'note'];

    public function codes(): View
    {
        return view('admin.settings.codes', [
            'codes' => InviteCode::query()->withCount('users')->latest()->get(),
        ]);
    }

    public function createCodes(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'how_many' => ['required', 'integer', 'min:1', 'max:20'],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'note' => ['nullable', 'string', 'max:120'],
            'expires_on' => ['nullable', 'date', 'after_or_equal:today'],
        ], ['expires_on.after_or_equal' => 'Pick today or a later date.']);

        $made = [];
        for ($i = 0; $i < (int) $data['how_many']; $i++) {
            $code = InviteCode::create([
                'code' => InviteCode::generate(),
                'note' => ($data['note'] ?? null) ?: null,
                'max_uses' => $data['max_uses'] ?? null,
                'expires_at' => ! empty($data['expires_on']) ? \Illuminate\Support\Carbon::parse($data['expires_on'])->endOfDay() : null,
                'created_by' => $request->user()->id,
            ]);
            $made[] = $code->code;
            AuditLogger::log($request->user(), 'invite_code.create', $code, null, ['code' => $code->code, 'max_uses' => $code->max_uses]);
        }

        return redirect()->route('admin.settings.codes')->with('status', (count($made) === 1 ? 'New code: ' : 'New codes: ').implode(', ', $made));
    }

    public function revoke(Request $request, InviteCode $code): RedirectResponse
    {
        if ($code->revoked_at === null) {
            $code->update(['revoked_at' => now()]);
            AuditLogger::log($request->user(), 'invite_code.revoke', $code, ['code' => $code->code], ['revoked' => true]);
        }

        return redirect()->route('admin.settings.codes')->with('status', "Code {$code->code} is turned off. Accounts already made with it are not affected.");
    }

    public function deposit(SettingsRepository $settings): View
    {
        return view('admin.settings.deposit', [
            'd' => self::depositDefaults($settings),
            'auto' => $settings->bool(self::AUTO_PAIR_KEY, true),
            'missing' => self::missingForAuto($settings),
        ]);
    }

    public function saveDeposit(Request $request, SettingsRepository $settings): RedirectResponse
    {
        $data = $request->validate([
            'partner_name' => ['nullable', 'string', 'max:120'],
            'partner_city' => ['nullable', 'string', 'max:80'],
            'account_holder' => ['nullable', 'string', 'max:120'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'routing_number' => ['nullable', 'string', 'max:40'],
            'account_number' => ['nullable', 'string', 'max:40'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $settings->set(self::DEPOSIT_KEY, array_map(fn ($v) => trim((string) $v), $data + array_fill_keys(self::DEPOSIT_FIELDS, '')), $request->user());
        $settings->set(self::AUTO_PAIR_KEY, $request->boolean('auto_pair'), $request->user());

        $status = 'Default deposit details saved. New pairings start with them.';
        if ($request->boolean('auto_pair') && self::missingForAuto($settings) !== []) {
            $status .= ' Automatic pairing waits until the co-partner name, account holder, bank name and account number are filled in.';
        }

        return redirect()->route('admin.settings.deposit')->with('status', $status);
    }

    /** @return array<string, string> */
    public static function depositDefaults(?SettingsRepository $settings = null): array
    {
        $saved = ($settings ?? app(SettingsRepository::class))->get(self::DEPOSIT_KEY);

        return array_merge(array_fill_keys(self::DEPOSIT_FIELDS, ''), is_array($saved) ? $saved : []);
    }

    /** @return list<string> Labels of the default details that are still empty. */
    public static function missingForAuto(?SettingsRepository $settings = null): array
    {
        $d = self::depositDefaults($settings);
        $labels = ['partner_name' => 'co-partner name', 'account_holder' => 'account holder', 'bank_name' => 'bank name', 'account_number' => 'account number'];

        return array_values(array_map(fn ($k) => $labels[$k], array_filter(self::REQUIRED_FOR_AUTO, fn ($k) => trim((string) $d[$k]) === '')));
    }

    /** True when new members should be paired automatically: the switch is on and the details it needs are filled in. */
    public static function autoPairReady(?SettingsRepository $settings = null): bool
    {
        $settings ??= app(SettingsRepository::class);

        return $settings->bool(self::AUTO_PAIR_KEY, true) && self::missingForAuto($settings) === [];
    }
}
