<?php

namespace App\Mail;

class WithdrawalRequestedAdmin extends BrandedMail
{
    public function __construct(protected string $name, protected int $amountCents, protected string $reference) {}

    public function template(): string
    {
        return 'withdrawal_requested_admin';
    }

    protected function subjectLine(): string
    {
        return 'A withdrawal needs approval';
    }

    protected function data(): array
    {
        return ['name' => $this->name, 'amount' => $this->money($this->amountCents), 'reference' => $this->reference, 'url' => route('admin.withdrawals')];
    }
}
