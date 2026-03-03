<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZiinaService
{
    public function createPaymentIntent(
        float $amount,
        string $currencyCode,
        ?string $successUrl = null,
        ?string $cancelUrl = null,
        ?string $failureUrl = null,
        ?string $message = null,
        bool $test = false
    ): ?array {
        $baseUrl = rtrim(config('ziina.api_base', config('services.ziina.api_base')), '/');
        $apiKey = config('ziina.api_key', config('services.ziina.api_key'));
        if (! $apiKey) {
            Log::warning('Ziina: ZIINA_API_KEY not set');
            return null;
        }

        // Amount in base units (e.g. AED: 1 AED = 100 fils, so 10.50 AED = 1050)
        $amountInBaseUnits = (int) round($amount * 100);

        if ($amountInBaseUnits < 1) {
            Log::warning('Ziina: amount too small', ['amount' => $amount]);
            return null;
        }

        $successUrl = $successUrl ?? config('ziina.success_url', config('services.ziina.success_url'));
        $cancelUrl = $cancelUrl ?? config('ziina.cancel_url', config('services.ziina.cancel_url'));
        $test = $test || config('ziina.mode', config('services.ziina.mode', 'sandbox')) === 'sandbox';

        $payload = [
            'amount' => $amountInBaseUnits,
            'currency_code' => strtoupper($currencyCode),
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'test' => $test,
        ];
        if ($failureUrl) {
            $payload['failure_url'] = $failureUrl;
        }
        if ($message !== null && $message !== '') {
            $payload['message'] = $message;
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->contentType('application/json')
            ->withHeaders([
                'User-Agent' => config('ziina.user_agent', 'Khararif-Backend/1.0 (+https://kararif.wecan.ae)'),
            ])
            ->timeout(15)
            ->post($baseUrl . '/payment_intent', $payload);

        if (! $response->successful()) {
            Log::warning('Ziina create payment_intent failed', [
                'status' => $response->status(),
                'url' => $baseUrl . '/payment_intent',
                'body' => strlen($response->body()) > 500 ? substr($response->body(), 0, 500) . '...' : $response->body(),
            ]);
            return null;
        }

        $data = $response->json();
        return [
            'id' => $data['id'] ?? null,
            'redirect_url' => $data['redirect_url'] ?? null,
            'status' => $data['status'] ?? null,
        ];
    }
}
