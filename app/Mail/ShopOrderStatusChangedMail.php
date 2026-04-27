<?php

namespace App\Mail;

use App\Models\ShopOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShopOrderStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ShopOrder $order,
        public string $previousStatus
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Status Updated - ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shop-order-status-changed',
            text: 'emails.shop-order-status-changed-text',
        );
    }

    public function currentStatusLabel(): string
    {
        return $this->statusLabel((string) $this->order->status);
    }

    public function previousStatusLabel(): string
    {
        return $this->statusLabel($this->previousStatus);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            ShopOrder::STATUS_PENDING_PAYMENT => 'Pending payment',
            ShopOrder::STATUS_NEW_ORDER => 'New order',
            ShopOrder::STATUS_CONFIRMED => 'Confirmed',
            ShopOrder::STATUS_ON_DELIVERY => 'On delivery',
            ShopOrder::STATUS_DELIVERED => 'Delivered',
            ShopOrder::STATUS_FAILED => 'Failed',
            ShopOrder::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
