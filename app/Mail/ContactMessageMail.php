<?php

namespace App\Mail;

use App\Models\ContactUs;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $sourceLabel;

    public function __construct(
        public ContactUs $contact
    ) {
        $labels = ['mobile' => 'تطبيق الجوال', 'tv' => 'تطبيق التلفزيون', 'other' => 'أخرى'];
        $this->sourceLabel = $labels[$contact->source ?? ''] ?? ($contact->source ?? '—');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[خراريف] رسالة تواصل جديد: ' . ($this->contact->subject ?: 'بدون موضوع'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-message',
        );
    }
}
