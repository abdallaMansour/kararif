<?php

namespace App\Services\Shop;

use App\Models\ShopOrder;
use App\Models\ShopProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ShopOrderService
{
    public function createPendingOrder(array $payload): ShopOrder
    {
        $requestedItems = collect($payload['items'] ?? []);
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
            $quantity = (int) $item['quantity'];
            $unitPrice = (float) $product->price_aed;
            $lineTotal = round($unitPrice * $quantity, 2);
            $subtotal += $lineTotal;

            $items[] = [
                'shop_product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price_aed' => $unitPrice,
                'line_total_aed' => $lineTotal,
            ];
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
}
