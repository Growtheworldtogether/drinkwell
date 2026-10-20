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
use App\Models\User;
use App\Models\VendingMachine;
use Illuminate\Support\Facades\DB;

/**
 * Pairs a new senior with a machine and a co-partner.
 *
 * Nobody waits in a pool. The second slot is filled at once by a simulated co-partner made from the seeded generator,
 * and the screens say so. All of it runs in one transaction that locks the machine row, so two sign-ups can never take
 * the same machine, and the unique keys on machine_ownerships are the second line of defence.
 */
class PairingService
{
    public function __construct(private SimulatedPartners $partners, private MailDispatcher $mail) {}

    public function pair(User $user, Tier $tier): MachineOwnership
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

            $machine->update(['assignment_status' => AssignmentStatus::Paired]);
            $user->forceFill(['role' => Role::Partner])->save();

            app(MachineEngine::class)->log($machine, 'pairing', "Paired with {$partner->name} (simulated).");

            return $mine;
        });

        // Mail goes out after the commit, so a mail outage cannot undo the pairing.
        $ownership->loadMissing('machine');
        $partner = $this->partnerOf($ownership);
        $this->mail->sendQuietly(new PairingDossier($user, $partner, $ownership), $user);
        $this->mail->logSimulated(new PairingNoticeToPartner($user, $partner, $ownership), $partner);

        return $ownership;
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
        return sprintf('DW-%04d-%05d', $mine->machine_id, $mine->user_id);
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
            app(MachineEngine::class)->log($machine, 'settlement_confirmed', 'Deposit confirmed. Payouts start the next day.');
        });
    }
}
