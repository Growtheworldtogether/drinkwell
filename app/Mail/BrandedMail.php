<?php

namespace App\Mail;

use App\Services\BrandService;
use App\Services\SettingsRepository;
use App\Support\Money;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Base for every template in SPEC 5.5.
 *
 * Every subject comes from the brand settings. The sender name does too, unless Settings, Email sender names another. No class or
 * template types the product name. The sender address stays in .env because it
 * has to match the SMTP account.
 */
abstract class BrandedMail extends Mailable
{
    /** Template key as listed in SPEC 5.5 and logged in mail_log, for example `otp_verify_email`. */
    abstract public function template(): string;

    /** Subject without the brand prefix. Sentence case, no em dashes. */
    abstract protected function subjectLine(): string;

    /** @return array<string, mixed> */
    protected function data(): array
    {
        return [];
    }

    /** Subject as sent: `{brand.name}: {subject}`. */
    public function subjectText(): string
    {
        return app(BrandService::class)->name().': '.$this->subjectLine();
    }

    /** The name recipients see in their inbox: the one saved under Settings, Email sender, else the platform name. */
    public function senderName(): string
    {
        return app(SettingsRepository::class)->nullableString('mail.from_name') ?? app(BrandService::class)->name();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), $this->senderName()),
            subject: $this->subjectText(),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.'.$this->template(),
            with: $this->data() + ['brand' => app(BrandService::class)->brand()],
        );
    }

    protected function money(int $cents): string
    {
        return Money::format($cents);
    }
}
