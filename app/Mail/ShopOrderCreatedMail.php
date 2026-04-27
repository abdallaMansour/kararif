<?php

namespace App\Mail;

use App\Models\ShopOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShopOrderCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ShopOrder $order,
        public bool $isAdminRecipient = false
    ) {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $this->logoUrl = $baseUrl !== '' ? ($baseUrl . '/animated-logo-ii.gif') : asset('animated-logo-ii.gif');
    }

    public string $logoUrl = '';

    public function envelope(): Envelope
    {
        $subjectPrefix = $this->isAdminRecipient ? 'طلب جديد' : 'تم استلام طلبك';

        return new Envelope(
            from: new Address((string) config('mail.from.address'), 'خراريف'),
            subject: $subjectPrefix . ' - ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shop-order-created',
            text: 'emails.shop-order-created-text',
        );
    }
}
