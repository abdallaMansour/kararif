<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Mail\ShopOrderPaidMail;
use App\Models\ShopOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ShopPaymentWebhookController extends Controller
{
    public function ziina(Request $request): JsonResponse
    {
        $secret = config('ziina.webhook_secret');
        if ($secret) {
            $signature = $request->header('X-Hmac-Signature');
            if (! $signature || ! $this->verifyHmac($request->getContent(), $signature, $secret)) {
                Log::warning('Shop Ziina webhook signature invalid');
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $payload = $request->all();
        if (($payload['event'] ?? null) !== 'payment_intent.status.updated') {
            return response()->json(['received' => true], 200);
        }

        $intent = (array) ($payload['data'] ?? []);
        $intentId = (string) ($intent['id'] ?? '');
        if ($intentId === '') {
            return response()->json(['error' => 'Missing intent id'], 400);
        }

        $order = ShopOrder::where('gateway_payment_intent_id', $intentId)->first();
        if (! $order) {
            Log::warning('Shop Ziina webhook order not found', ['intent_id' => $intentId]);
            return response()->json(['received' => true], 200);
        }

        $status = (string) ($intent['status'] ?? '');
        if ($status === 'completed') {
            if ($order->status !== ShopOrder::STATUS_PAID) {
                $order->update([
                    'status' => ShopOrder::STATUS_PAID,
                    'gateway_reference' => $intentId,
                    'paid_at' => now(),
                ]);

                $order->load('items.product');
                Mail::to($order->customer_email)->send(new ShopOrderPaidMail($order));
            }
        } elseif (in_array($status, ['failed', 'cancelled'], true)) {
            $order->update([
                'status' => $status === 'cancelled'
                    ? ShopOrder::STATUS_CANCELLED
                    : ShopOrder::STATUS_FAILED,
            ]);
        }

        return response()->json(['received' => true], 200);
    }

    private function verifyHmac(string $body, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $body, $secret);
        return hash_equals($expected, $signature);
    }
}
