<?php

namespace App\Services\Shop;

use App\Mail\ShopOrderCreatedMail;
use App\Mail\ShopOrderStatusChangedMail;
use App\Models\ShopOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ShopOrderMailService
{
    public function sendOrderCreatedMails(ShopOrder $order): void
    {
        $order->loadMissing('items.product');

        $adminEmail = (string) (config('mail.shop_orders_admin_email') ?: '');
        if ($adminEmail !== '') {
            $this->sendMail($adminEmail, new ShopOrderCreatedMail($order, true), 'shop order admin create mail failed');
        }

        $customerEmail = trim((string) $order->customer_email);
        if ($customerEmail !== '') {
            $this->sendMail($customerEmail, new ShopOrderCreatedMail($order, false), 'shop order customer create mail failed');
        }
    }

    public function sendStatusChangedMail(ShopOrder $order, string $previousStatus): void
    {
        $customerEmail = trim((string) $order->customer_email);
        if ($customerEmail === '' || $previousStatus === $order->status) {
            return;
        }

        $order->loadMissing('items.product');
        $this->sendMail(
            $customerEmail,
            new ShopOrderStatusChangedMail($order, $previousStatus),
            'shop order status mail failed'
        );
    }

    private function sendMail(string $to, mixed $mailable, string $logMessage): void
    {
        try {
            Mail::to($to)->send($mailable);
        } catch (\Throwable $th) {
            Log::warning($logMessage, [
                'to' => $to,
                'order_id' => $mailable->order->id ?? null,
                'error' => $th->getMessage(),
            ]);
        }
    }
}
