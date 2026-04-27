<?php

namespace App\Mail;

use App\Models\Opinion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OpinionMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Opinion $opinion
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), 'خراريف'),
            subject: '[خراريف] رأي جديد من العملاء',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.opinion-message',
        );
    }
}
