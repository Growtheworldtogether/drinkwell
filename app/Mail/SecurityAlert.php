<?php

namespace App\Mail;

class SecurityAlert extends BrandedMail
{
    public function __construct(protected string $adminEmail, protected int $attempts, protected int $minutes) {}

    public function template(): string
    {
        return 'security_alert';
    }

    protected function subjectLine(): string
    {
        return 'Admin sign-in locked';
    }

    protected function data(): array
    {
        return ['adminEmail' => $this->adminEmail, 'attempts' => $this->attempts, 'minutes' => $this->minutes];
    }
}
