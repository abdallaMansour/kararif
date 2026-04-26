<?php

namespace App\Services\Shop;

class OrderConfirmationTokenService
{
    public function issue(int $orderId, int $ttlMinutes = 60): string
    {
        $exp = now()->addMinutes($ttlMinutes)->timestamp;
        $payload = $orderId . '.' . $exp;
        $signature = hash_hmac('sha256', $payload, $this->secret());

        return base64_encode($payload . '.' . $signature);
    }

    public function verify(int $orderId, string $token): bool
    {
        $decoded = base64_decode($token, true);
        if (! $decoded) {
            return false;
        }

        $parts = explode('.', $decoded);
        if (count($parts) !== 3) {
            return false;
        }

        [$tokenOrderId, $exp, $signature] = $parts;
        if ((int) $tokenOrderId !== $orderId || ! ctype_digit($exp)) {
            return false;
        }
        if ((int) $exp < now()->timestamp) {
            return false;
        }

        $payload = $tokenOrderId . '.' . $exp;
        $expected = hash_hmac('sha256', $payload, $this->secret());

        return hash_equals($expected, $signature);
    }

    private function secret(): string
    {
        return (string) config('app.key');
    }
}
