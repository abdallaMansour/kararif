<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Handle Ziina webhook: payment_intent.status.updated
     * Call this URL from Ziina dashboard webhook configuration.
     */
    public function ziina(Request $request): JsonResponse
    {
        $payload = $request->all();
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];

        if ($event !== 'payment_intent.status.updated') {
            return response()->json(['received' => true], 200);
        }

        $status = $data['status'] ?? null;
        if ($status !== 'completed') {
            return response()->json(['received' => true], 200);
        }

        $paymentIntentId = $data['id'] ?? null;
        if (empty($paymentIntentId)) {
            Log::warning('Ziina webhook: payment_intent.status.updated with status completed but missing id', [
                'data' => $data,
            ]);
            return response()->json(['error' => 'Missing payment intent id'], 400);
        }

        $secret = config('ziina.webhook_secret');
        if ($secret) {
            $signature = $request->header('X-Hmac-Signature');
            if (!$signature || !$this->verifyHmac($request->getContent(), $signature, $secret)) {
                Log::warning('Ziina webhook: HMAC verification failed');
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $payment = $this->paymentService->completePaymentByExternalId((string) $paymentIntentId);
        if ($payment) {
            Log::info('Ziina webhook: payment completed', [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
            ]);
        } else {
            Log::warning('Ziina webhook: no payment found for intent', ['payment_intent_id' => $paymentIntentId]);
        }

        return response()->json(['received' => true], 200);
    }

    private function verifyHmac(string $body, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $body, $secret);
        return hash_equals($expected, $signature);
    }
}
