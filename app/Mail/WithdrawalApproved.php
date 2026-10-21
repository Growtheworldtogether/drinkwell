<?php

namespace App\Mail;

class WithdrawalApproved extends BrandedMail
{
    public function __construct(protected int $amountCents, protected string $reference, protected string $paidReference, protected string $bank, protected string $last4) {}

    public function template(): string
    {
        return 'withdrawal_approved';
    }

    protected function subjectLine(): string
    {
        return 'Your withdrawal was approved';
    }

    protected function data(): array
    {
        return ['amount' => $this->money($this->amountCents), 'reference' => $this->reference, 'paidReference' => $this->paidReference, 'bank' => $this->bank, 'last4' => $this->last4];
    }
}
