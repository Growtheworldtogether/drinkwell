<?php

namespace App\Mail;

use App\Models\MachineOwnership;
use App\Models\User;
use App\Services\PairingService;

/** The matching message for the simulated co-partner. It is logged as "simulated" and never sent. */
class PairingNoticeToPartner extends BrandedMail
{
    public function __construct(protected User $user, protected User $partner, protected MachineOwnership $ownership) {}

    public function template(): string
    {
        return 'pairing_notice_partner';
    }

    protected function subjectLine(): string
    {
        return 'You were paired with a new co-partner';
    }

    protected function data(): array
    {
        return [
            'partnerName' => $this->partner->name,
            'userName' => $this->user->name,
            'userEmail' => $this->user->email,
            'machine' => $this->ownership->machine,
            'reference' => PairingService::reference($this->ownership),
        ];
    }
}
