<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A website enquiry about a specific property, delivered to the listing's
 * agent(s). The visitor is set as reply-to so the agent can answer directly.
 */
class PropertyEnquiry extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public string $senderPhone,
        public string $body,
        public string $propertyTitle,
        public ?string $propertyReference,
        public string $propertyUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Property enquiry: '.$this->propertyTitle,
            replyTo: [new Address($this->senderEmail, $this->senderName)],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.property-enquiry',
        );
    }
}
