<?php

namespace App\Mail;

class OtpResetPassword extends BrandedMail
{
    public function __construct(public readonly string $code, protected int $minutes = 10) {}

    public function template(): string
    {
        return 'otp_reset_password';
    }

    protected function subjectLine(): string
    {
        return 'Your password reset code';
    }

    protected function data(): array
    {
        return ['code' => $this->code, 'minutes' => $this->minutes];
    }
}
