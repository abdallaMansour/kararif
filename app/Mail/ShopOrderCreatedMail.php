<?php

namespace App\Mail;

use App\Models\ShopOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShopOrderCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ShopOrder $order,
        public bool $isAdminRecipient = false
    ) {}

    public function envelope(): Envelope
    {
        $subjectPrefix = $this->isAdminRecipient ? 'New Order Received' : 'Order Received';

        return new Envelope(
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
