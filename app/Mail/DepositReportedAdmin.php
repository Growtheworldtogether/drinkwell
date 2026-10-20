<?php

namespace App\Mail;

use App\Models\MachineOwnership;
use App\Models\User;

class DepositReportedAdmin extends BrandedMail
{
    public function __construct(protected User $partner, protected MachineOwnership $ownership) {}

    public function template(): string
    {
        return 'deposit_reported_admin';
    }

    protected function subjectLine(): string
    {
        return 'A partner reports a deposit';
    }

    protected function data(): array
    {
        return [
            'partnerName' => $this->partner->name,
            'partnerEmail' => $this->partner->email,
            'amount' => $this->money($this->ownership->capital_cents),
            'url' => route('admin.machines.show', $this->ownership->machine_id),
        ];
    }
}
