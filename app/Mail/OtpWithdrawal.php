<?php

namespace App\Mail;

class OtpWithdrawal extends BrandedMail
{
    public function __construct(public readonly string $code, protected int $amountCents, protected int $minutes = 10) {}

    public function template(): string
    {
        return 'otp_withdrawal';
    }

    protected function subjectLine(): string
    {
        return 'Your withdrawal code';
    }

    protected function data(): array
    {
        return ['code' => $this->code, 'amount' => $this->money($this->amountCents), 'minutes' => $this->minutes];
    }
}
