<?php

namespace App\Console\Commands;

use App\Exceptions\MailSendFailed;
use App\Mail\MailConnectionTest;
use App\Services\MailDispatcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('drinkwell:mail-test {email : Address that receives the test message}')]
#[Description('Send a test email through the configured mail server and log the result')]
class MailTestCommand extends Command
{
    public function handle(MailDispatcher $mail): int
    {
        $email = (string) $this->argument('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->components->error('That is not a valid email address.');

            return self::FAILURE;
        }

        $mailer = (string) config('mail.default');
        $host = (string) config("mail.mailers.{$mailer}.host", '-');

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->components->warn("MAIL_MAILER is {$mailer}. Nothing reaches a real inbox. Set MAIL_MAILER=smtp in .env.");
        }

        try {
            $mail->send(new MailConnectionTest($mailer, $host), $email);
        } catch (MailSendFailed $e) {
            $this->components->error('The mail server refused the message: '.($e->getPrevious()?->getMessage() ?? $e->getMessage()));
            $this->line('Check MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION and MAIL_FROM_ADDRESS in .env.');

            return self::FAILURE;
        }

        $this->components->info("Test message sent to {$email} through {$mailer} ({$host}). Check the inbox.");

        return self::SUCCESS;
    }
}
