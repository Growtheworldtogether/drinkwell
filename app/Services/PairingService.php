<?php

namespace App\Services;

use App\Enums\AssignmentStatus;
use App\Enums\Role;
use App\Enums\SettlementStatus;
use App\Enums\Tier;
use App\Exceptions\NoMachineAvailable;
use App\Mail\PairingDossier;
use App\Mail\PairingNoticeToPartner;
use App\Models\MachineOwnership;
use App\Models\PairingRequest;
use App\Models\User;
use App\Models\VendingMachine;
use Illuminate\Support\Facades\DB;

/**
 * Pairs a new member with a machine and a co-partner.
 *
 * Nobody waits in a pool. The second slot is filled at once by a simulated co-partner made from the seeded generator,
 * and the screens say so. All of it runs in one transaction that locks the machine row, so two sign-ups can never take
 * the same machine, and the unique keys on machine_ownerships are the second line of defence.
 */
class PairingService
{
    public function __construct(private SimulatedPartners $partners, private MailDispatcher $mail) {}

    public function pair(User $user, Tier $tier, bool $notify = true): MachineOwnership
    {
        if ($user->ownerships()->exists()) {
            throw new \DomainException('This account is already paired with a machine.');
        }

        $ownership = DB::transaction(function () use ($user, $tier) {
            $machine = VendingMachine::query()
                ->where('tier', $tier)
                ->where('assignment_status', AssignmentStatus::Open)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $machine) {
                throw new NoMachineAvailable('No '.$tier->label().' is free right now.');
            }

            $partner = $this->partners->make($this->partners->nextNumber(), $machine->city);
            $from = $machine->operating_date->copy()->addDay();

            $mine = MachineOwnership::create([
                'machine_id' => $machine->id,
                'user_id' => $user->id,
                'slot_no' => 1,
                'capital_cents' => $tier->stakeCents(),
                'settlement_status' => SettlementStatus::PendingSettlement,
                'earning_from' => $from,
            ]);

            // The simulated co-partner is shown as already funded.
            MachineOwnership::create([
                'machine_id' => $machine->id,
                'user_id' => $partner->id,
                'slot_no' => 2,
                'capital_cents' => $tier->stakeCents(),
                'settlement_status' => SettlementStatus::Settled,
                'earning_from' => $from,
                'settled_at' => now(),
            ]);

            // The member stays an applicant until the deposit is confirmed; settle() opens the dashboard.
            $machine->update(['assignment_status' => AssignmentStatus::Paired]);

            app(MachineEngine::class)->log($machine, 'pairing', "Paired with {$partner->name}.");

            return $mine;
        });

        // Mail goes out after the commit, so a mail outage cannot undo the pairing.
        $ownership->loadMissing('machine');
        $partner = $this->partnerOf($ownership);
        if ($notify) {
            $this->mail->sendQuietly(new PairingDossier($user, $partner, $ownership), $user);
            $this->mail->logSimulated(new PairingNoticeToPartner($user, $partner, $ownership), $partner);
        }

        return $ownership;
    }

    /** A new member asks to be paired. Nothing is assigned until an administrator pairs them. */
    public function request(User $user, Tier $tier): PairingRequest
    {
        return PairingRequest::firstOrCreate(['user_id' => $user->id], ['tier' => $tier->value, 'status' => PairingRequest::FINDING]);
    }

    /**
     * An administrator pairs a waiting member: assigns a machine, shows the co-partner under the name given, stores the
     * payment details the member will see, and emails them.
     *
     * @param  array{partner_name: string, partner_city: ?string, account_holder: string, bank_name: string, routing_number: ?string, account_number: string, reference: ?string, note: ?string}  $d
     */
    public function assign(PairingRequest $req, array $d): MachineOwnership
    {
        $user = $req->user;
        $mine = $this->pair($user, Tier::from($req->tier), notify: false);

        $partner = $this->partnerOf($mine);
        $partner->update(['name' => $d['partner_name']]);
        if (! empty($d['partner_city'])) {
            $partner->profile?->update(['city' => $d['partner_city']]);
        }

        $req->update(['status' => PairingRequest::PAIRED, 'paired_at' => now()] + $this->details($d, $mine));

        // Style 2 sends nothing by email: the member sees everything in the three payment screens.
        if (! \App\Support\DepositStyle::isContact()) {
            $mine->loadMissing('machine');
            $this->mail->sendQuietly(new PairingDossier($user, $partner->fresh('profile'), $mine, $req->fresh()), $user);
        }

        return $mine;
    }

    /** Fixes a typo in the details after pairing and emails the member the corrected version. */
    public function updateDetails(PairingRequest $req, array $d): void
    {
        $mine = MachineOwnership::query()->with('machine')->where('user_id', $req->user_id)->firstOrFail();
        $partner = $this->partnerOf($mine);
        $partner->update(['name' => $d['partner_name']]);
        if (! empty($d['partner_city'])) {
            $partner->profile?->update(['city' => $d['partner_city']]);
        }

        $req->update($this->details($d, $mine));
        if (! \App\Support\DepositStyle::isContact()) {
            $this->mail->sendQuietly(new PairingDossier($req->user, $partner->fresh('profile'), $mine, $req->fresh(), updated: true), $req->user);
        }
    }

    /** @return array<string, mixed> */
    private function details(array $d, MachineOwnership $mine): array
    {
        $number = trim((string) ($d['account_number'] ?? ''));

        return [
            'partner_name' => trim($d['partner_name']),
            'partner_city' => ($d['partner_city'] ?? null) ?: null,
            'partner_email' => ($d['partner_email'] ?? null) ?: null,
            'account_holder' => trim((string) ($d['account_holder'] ?? '')) ?: null,
            'bank_name' => trim((string) ($d['bank_name'] ?? '')) ?: null,
            'routing_number' => ($d['routing_number'] ?? null) ?: null,
            'account_number' => $number !== '' ? $number : null,
            'account_last4' => $number !== '' ? substr($number, -4) : null,
            'reference' => ($d['reference'] ?? null) ?: self::reference($mine),
            'note' => ($d['note'] ?? null) ?: null,
        ];
    }

    public function partnerOf(MachineOwnership $mine): User
    {
        return User::query()
            ->whereIn('id', MachineOwnership::query()->where('machine_id', $mine->machine_id)->where('user_id', '!=', $mine->user_id)->select('user_id'))
            ->with('profile')
            ->firstOrFail();
    }

    /** Escrow reference printed in the instructions. Fictional, derived from the ids. */
    public static function reference(MachineOwnership $mine): string
    {
        return sprintf('DW-%06d-%04d', 318000 + $mine->machine_id * 97, 1100 + $mine->user_id * 13);
    }

    /** Demo settlement: the deposit is treated as received. Earning starts the next business day of the machine. */
    public function settle(MachineOwnership $mine): void
    {
        DB::transaction(function () use ($mine) {
            $row = MachineOwnership::query()->whereKey($mine->id)->lockForUpdate()->firstOrFail();
            if ($row->settlement_status === SettlementStatus::Settled) {
                return;
            }

            $machine = VendingMachine::query()->whereKey($row->machine_id)->lockForUpdate()->firstOrFail();
            $row->update([
                'settlement_status' => SettlementStatus::Settled,
                'settled_at' => now(),
                'earning_from' => $machine->operating_date->copy()->addDay(),
            ]);
            $machine->update(['assignment_status' => AssignmentStatus::Active]);
            // Deposit confirmed: the member becomes a co-partner and the dashboard opens for them.
            User::query()->whereKey($row->user_id)->where('role', Role::Applicant)->update(['role' => Role::Partner]);
            app(MachineEngine::class)->log($machine, 'settlement_confirmed', 'Deposit confirmed. Payouts start the next day.');
        });
    }
}
