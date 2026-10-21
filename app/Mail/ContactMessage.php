<?php

namespace App\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;

class ContactMessage extends BrandedMail
{
    public function __construct(protected string $name, protected string $email, protected string $topic, protected string $body) {}

    public function template(): string
    {
        return 'contact_message';
    }

    protected function subjectLine(): string
    {
        return 'Message from the Contact page: '.$this->topic;
    }

    public function envelope(): Envelope
    {
        $base = parent::envelope();

        return new Envelope(from: $base->from, replyTo: [new Address($this->email, $this->name)], subject: $this->subjectText());
    }

    protected function data(): array
    {
        return ['name' => $this->name, 'email' => $this->email, 'topic' => $this->topic, 'body' => $this->body];
    }
}
