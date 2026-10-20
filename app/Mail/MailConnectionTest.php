<?php

namespace App\Mail;

/** Sent only by the mail-test console command. Not one of the SPEC 5.5 templates. */
class MailConnectionTest extends BrandedMail
{
    public function __construct(protected string $driver, protected string $host) {}

    public function template(): string
    {
        return 'mail_test';
    }

    protected function subjectLine(): string
    {
        return 'Mail delivery test';
    }

    protected function data(): array
    {
        return ['mailer' => $this->driver, 'host' => $this->host, 'sentAt' => now()->toDayDateTimeString()];
    }
}
