<?php

namespace App\Http\Controllers\Shop;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CheckoutRequest;
use App\Models\ShopOrder;
use App\Services\Shop\OrderConfirmationTokenService;
use App\Services\Shop\ShopOrderService;
use App\Services\ZiinaService;
use Illuminate\Http\JsonResponse;

class ShopOrderController extends Controller
{
    public function __construct(
        protected ShopOrderService $shopOrderService,
        protected OrderConfirmationTokenService $tokenService,
        protected ZiinaService $ziinaService
    ) {}

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $order = $this->shopOrderService->createPendingOrder($validated);
        } catch (\InvalidArgumentException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        $token = $this->tokenService->issue($order->id, (int) config('shop.confirmation_token_ttl_minutes', 60));
        $successBaseUrl = (string) ($validated['success_url'] ?? config('shop.frontend_confirmation_url'));
        $successUrl = $this->buildSuccessUrl($successBaseUrl, $order->id, $token);
        $cancelUrl = $validated['cancel_url'] ?? config('shop.frontend_cancel_url');

        $intent = $this->ziinaService->createPaymentIntent(
            $order->total_aed,
            'AED',
            $successUrl,
            $cancelUrl,
            null,
            'Order ' . $order->order_number
        );

        if (! $intent || empty($intent['redirect_url']) || empty($intent['id'])) {
            $order->update(['status' => ShopOrder::STATUS_FAILED]);
            return ApiResponse::error('Unable to create payment session', 502);
        }

        $order->update([
            'gateway_payment_intent_id' => (string) $intent['id'],
            'gateway_reference' => (string) ($intent['id'] ?? ''),
        ]);

        return ApiResponse::success([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'subtotal_aed' => $order->subtotal_aed,
            'shipping_fee_aed' => $order->shipping_fee_aed,
            'total_aed' => $order->total_aed,
            'payment_url' => $intent['redirect_url'],
            'confirmation_token' => $token,
        ]);
    }

    private function buildSuccessUrl(string $baseUrl, int $orderId, string $token): string
    {
        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl
            . $separator
            . 'order_id=' . $orderId
            . '&token=' . urlencode($token)
            . '&payment_intent_id={PAYMENT_INTENT_ID}';
    }

    public function show(int $orderId): JsonResponse
    {
        $token = (string) request()->query('token', '');
        if ($token === '' || ! $this->tokenService->verify($orderId, $token)) {
            return ApiResponse::error('Invalid or expired order token.', 403);
        }

        $order = ShopOrder::with('items.product')->findOrFail($orderId);

        return ApiResponse::success([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'customer' => [
                'full_name' => $order->customer_full_name,
                'phone' => $order->customer_phone,
                'email' => $order->customer_email,
            ],
            'delivery' => [
                'emirate' => $order->delivery_emirate,
                'area' => $order->delivery_area,
                'detail' => $order->delivery_detail,
            ],
            'items' => collect($order->items)->map(fn ($item) => [
                'product_id' => $item->shop_product_id,
                'name_ar' => $item->product?->name_ar,
                'quantity' => (int) $item->quantity,
                'unit_price_aed' => (float) $item->unit_price_aed,
                'line_total_aed' => (float) $item->line_total_aed,
                'image_url' => $item->product?->image_url,
            ])->values()->all(),
            'subtotal_aed' => (float) $order->subtotal_aed,
            'shipping_fee_aed' => (float) $order->shipping_fee_aed,
            'total_aed' => (float) $order->total_aed,
            'created_at' => $order->created_at,
        ]);
    }
}
