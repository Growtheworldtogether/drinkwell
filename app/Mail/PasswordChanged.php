<?php

namespace App\Mail;

class PasswordChanged extends BrandedMail
{
    public function __construct(protected string $userName, protected string $changedAt) {}

    public function template(): string
    {
        return 'password_changed';
    }

    protected function subjectLine(): string
    {
        return 'Your password was changed';
    }

    protected function data(): array
    {
        return ['userName' => $this->userName, 'changedAt' => $this->changedAt];
    }
}
