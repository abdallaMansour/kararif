<?php

namespace App\Services\Shop;

use App\Models\Setting;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ShopOrderService
{
    private const BOOK_SKU = 'book';
    private const STICKERS_SKU = 'stickers';
    private const PROMO_FREE_STICKER_PRODUCT_ID = 3;
    private const PROMO_FREE_STICKER_MAX_ORDER_ID = 30;

    public function createPendingOrder(array $payload): ShopOrder
    {
        $requestedItems = collect($payload['items'] ?? [])
            ->map(fn (array $item) => [
                'product_id' => (int) ($item['product_id'] ?? 0),
                'quantity' => (int) ($item['quantity'] ?? 0),
                'signature_names' => collect((array) ($item['signature_names'] ?? []))
                    ->map(fn ($name) => trim((string) $name))
                    ->filter()
                    ->values()
                    ->all(),
            ])
            ->groupBy('product_id')
            ->map(fn (Collection $group, int $productId) => [
                'product_id' => $productId,
                'quantity' => $group->sum('quantity'),
                'signature_names' => $group->pluck('signature_names')->flatten()->values()->all(),
            ])
            ->values();

        $productIds = $requestedItems->pluck('product_id')->unique()->values();

        /** @var Collection<int, ShopProduct> $products */
        $products = ShopProduct::query()
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->where('is_sellable', true)
            ->get()
            ->keyBy('id');

        if ($products->count() !== $productIds->count()) {
            throw new \InvalidArgumentException('One or more selected products are not sellable.');
        }

        $subtotal = 0.0;
        $items = [];
        foreach ($requestedItems as $item) {
            $product = $products->get((int) $item['product_id']);
            $quantity = max(1, (int) $item['quantity']);
            $signatureNames = collect((array) ($item['signature_names'] ?? []))
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->values();

            if ((string) $product->sku === self::BOOK_SKU && $signatureNames->count() > $quantity) {
                throw new \InvalidArgumentException('Signature names count cannot exceed ordered book quantity.');
            }

            if ((string) $product->sku !== self::BOOK_SKU && $signatureNames->isNotEmpty()) {
                throw new \InvalidArgumentException('Signature names are allowed only for books.');
            }

            $unitPrice = (float) $product->price_aed;
            $lineTotal = round($unitPrice * $quantity, 2);
            $subtotal += $lineTotal;

            $items[] = [
                'shop_product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price_aed' => $unitPrice,
                'line_total_aed' => $lineTotal,
                'signature_names' => $signatureNames->isEmpty() ? null : $signatureNames->values()->all(),
            ];
        }

        $containsBook = collect($items)->contains(function (array $item) use ($products): bool {
            $product = $products->get((int) $item['shop_product_id']);
            return (string) ($product?->sku ?? '') === self::BOOK_SKU;
        });

        if ($containsBook && $this->giftStickerEnabled()) {
            $stickerProduct = ShopProduct::query()
                ->where('sku', self::STICKERS_SKU)
                ->where('is_active', true)
                ->first();

            if ($stickerProduct) {
                $items[] = [
                    'shop_product_id' => $stickerProduct->id,
                    'quantity' => 1,
                    'unit_price_aed' => 0.0,
                    'line_total_aed' => 0.0,
                    'signature_names' => null,
                ];
            }
        }

        $shippingFee = (float) config('shop.shipping_fee_aed', 30);
        $subtotal = round($subtotal, 2);
        $total = round($subtotal + $shippingFee, 2);

        return DB::transaction(function () use ($payload, $items, $subtotal, $shippingFee, $total) {
            $order = ShopOrder::create([
                'order_number' => $this->generateOrderNumber(),
                'status' => ShopOrder::STATUS_PENDING_PAYMENT,
                'customer_full_name' => $payload['customer']['full_name'],
                'customer_phone' => $payload['customer']['phone'],
                'customer_email' => $payload['customer']['email'],
                'delivery_emirate' => $payload['delivery']['emirate'],
                'delivery_area' => $payload['delivery']['area'],
                'delivery_detail' => $payload['delivery']['detail'],
                'subtotal_aed' => $subtotal,
                'shipping_fee_aed' => $shippingFee,
                'total_aed' => $total,
                'gateway_name' => 'ziina',
            ]);

            if ($this->shouldAddPromoFreeSticker($order->id)) {
                $items[] = [
                    'shop_product_id' => self::PROMO_FREE_STICKER_PRODUCT_ID,
                    'quantity' => 1,
                    'unit_price_aed' => 0.0,
                    'line_total_aed' => 0.0,
                    'signature_names' => null,
                ];
            }

            $order->items()->createMany($items);

            return $order->fresh('items.product');
        });
    }

    private function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $suffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        return 'KHS-' . $date . '-' . $suffix;
    }

    private function giftStickerEnabled(): bool
    {
        $value = Setting::query()
            ->where('key', 'gift_sticker_with_book')
            ->whereNull('lang')
            ->value('value');

        return in_array((string) $value, ['1', 'true', 'on'], true);
    }

    private function shouldAddPromoFreeSticker(int $orderId): bool
    {
        if ($orderId < 1 || $orderId > self::PROMO_FREE_STICKER_MAX_ORDER_ID) {
            return false;
        }

        return ShopProduct::query()
            ->whereKey(self::PROMO_FREE_STICKER_PRODUCT_ID)
            ->where('is_active', true)
            ->exists();
    }
}
