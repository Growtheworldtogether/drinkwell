<?php

namespace App\Mail;

class OtpVerifyEmail extends BrandedMail
{
    public function __construct(public readonly string $code, protected int $minutes = 10) {}

    public function template(): string
    {
        return 'otp_verify_email';
    }

    protected function subjectLine(): string
    {
        return 'Your verification code';
    }

    protected function data(): array
    {
        return ['code' => $this->code, 'minutes' => $this->minutes];
    }
}
