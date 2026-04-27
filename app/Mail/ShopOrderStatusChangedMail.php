<?php

namespace App\Mail;

use App\Models\ShopOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShopOrderStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ShopOrder $order,
        public string $previousStatus
    ) {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $this->logoUrl = $baseUrl !== '' ? ($baseUrl . '/animated-logo-ii.gif') : asset('animated-logo-ii.gif');
    }

    public string $logoUrl = '';

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mail.from.address'), 'خراريف'),
            subject: 'تحديث حالة الطلب - ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.shop-order-status-changed',
            text: 'emails.shop-order-status-changed-text',
            with: [
                'previousStatusLabel' => $this->previousStatusLabel(),
                'currentStatusLabel' => $this->currentStatusLabel(),
            ],
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
            ShopOrder::STATUS_PENDING_PAYMENT => 'بانتظار الدفع',
            ShopOrder::STATUS_NEW_ORDER => 'طلب جديد',
            ShopOrder::STATUS_CONFIRMED => 'تم التأكيد',
            ShopOrder::STATUS_ON_DELIVERY => 'قيد التوصيل',
            ShopOrder::STATUS_DELIVERED => 'تم التسليم',
            ShopOrder::STATUS_FAILED => 'فشل الدفع',
            ShopOrder::STATUS_CANCELLED => 'تم الإلغاء',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
