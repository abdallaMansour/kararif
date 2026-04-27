<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ShopOrder;
use App\Services\Shop\ShopOrderMailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopPaymentWebhookController extends Controller
{
    public function __construct(
        protected ShopOrderMailService $shopOrderMailService
    ) {}

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
        $previousStatus = (string) $order->status;
        $becamePaid = false;
        if ($status === 'completed') {
            if ($order->paid_at === null) {
                $order->update([
                    'status' => ShopOrder::STATUS_NEW_ORDER,
                    'gateway_reference' => $intentId,
                    'paid_at' => now(),
                ]);
                $becamePaid = true;
            }
        } elseif (in_array($status, ['failed', 'cancelled'], true)) {
            $order->update([
                'status' => $status === 'cancelled'
                    ? ShopOrder::STATUS_CANCELLED
                    : ShopOrder::STATUS_FAILED,
            ]);
        }

        $order->refresh();
        if ($becamePaid) {
            $this->shopOrderMailService->sendOrderCreatedMails($order);
        }
        $this->shopOrderMailService->sendStatusChangedMail($order, $previousStatus);

        return response()->json(['received' => true], 200);
    }

    private function verifyHmac(string $body, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $body, $secret);
        return hash_equals($expected, $signature);
    }
}
