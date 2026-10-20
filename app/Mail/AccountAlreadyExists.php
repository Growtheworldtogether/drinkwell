<?php

namespace App\Mail;

class AccountAlreadyExists extends BrandedMail
{
    public function __construct(protected string $userName) {}

    public function template(): string
    {
        return 'account_already_exists';
    }

    protected function subjectLine(): string
    {
        return 'You already have an account';
    }

    protected function data(): array
    {
        return ['userName' => $this->userName, 'loginUrl' => route('login'), 'resetUrl' => route('password.request')];
    }
}
