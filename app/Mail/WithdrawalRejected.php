<?php

namespace App\Mail;

class WithdrawalRejected extends BrandedMail
{
    public function __construct(protected int $amountCents, protected string $reference, protected string $reason) {}

    public function template(): string
    {
        return 'withdrawal_rejected';
    }

    protected function subjectLine(): string
    {
        return 'Your withdrawal was declined';
    }

    protected function data(): array
    {
        return ['amount' => $this->money($this->amountCents), 'reference' => $this->reference, 'reason' => $this->reason];
    }
}
