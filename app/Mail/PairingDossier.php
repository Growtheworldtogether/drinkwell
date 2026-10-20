<?php

namespace App\Mail;

use App\Models\MachineOwnership;
use App\Models\PairingRequest;
use App\Models\User;
use App\Services\PairingService;

class PairingDossier extends BrandedMail
{
    public function __construct(protected User $user, protected User $partner, protected MachineOwnership $ownership, protected ?PairingRequest $request = null, protected bool $updated = false) {}

    public function template(): string
    {
        return 'pairing_dossier';
    }

    protected function subjectLine(): string
    {
        return $this->updated ? 'Updated payment details for your deposit' : 'Your machine and co-partner';
    }

    protected function data(): array
    {
        $m = $this->ownership->machine;
        $ref = $this->request?->reference ?: PairingService::reference($this->ownership);

        return [
            'userName' => $this->user->name,
            'partner' => $this->partner,
            'machine' => $m,
            'stake' => $this->money($this->ownership->capital_cents),
            'reference' => $ref,
            'request' => $this->request,
            'updated' => $this->updated,
            'statusUrl' => route('onboard.status'),
        ];
    }
}
