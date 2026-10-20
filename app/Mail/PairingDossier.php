<?php

namespace App\Mail;

use App\Models\MachineOwnership;
use App\Models\User;
use App\Services\PairingService;

class PairingDossier extends BrandedMail
{
    public function __construct(protected User $user, protected User $partner, protected MachineOwnership $ownership) {}

    public function template(): string
    {
        return 'pairing_dossier';
    }

    protected function subjectLine(): string
    {
        return 'Your machine and co-partner';
    }

    protected function data(): array
    {
        $m = $this->ownership->machine;
        $ref = PairingService::reference($this->ownership);

        $copy = "Hello {$this->partner->name},\n\n"
            ."I am {$this->user->name}, your co-partner on machine {$m->serial_no} ({$m->facility_name}, {$m->city}).\n"
            ."I will send my deposit of {$this->money($this->ownership->capital_cents)} to the escrow account with reference {$ref} and will tell you when it is done. "
            .'Please confirm when yours is sent too.'."\n\nThank you,\n{$this->user->name}";

        return [
            'userName' => $this->user->name,
            'partner' => $this->partner,
            'machine' => $m,
            'stake' => $this->money($this->ownership->capital_cents),
            'reference' => $ref,
            'copy' => $copy,
            'statusUrl' => route('onboard.status'),
        ];
    }
}
