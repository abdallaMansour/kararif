Order Confirmed
Order Number: {{ $order->order_number }}

Items:
@foreach($order->items as $item)
- {{ $item->product?->name_ar ?? 'Product #' . $item->shop_product_id }} x {{ $item->quantity }} - {{ number_format($item->line_total_aed, 2) }} AED
@endforeach

Subtotal: {{ number_format($order->subtotal_aed, 2) }} AED
Shipping: {{ number_format($order->shipping_fee_aed, 2) }} AED
Total: {{ number_format($order->total_aed, 2) }} AED

Delivery:
{{ $order->delivery_emirate }} - {{ $order->delivery_area }}
{{ $order->delivery_detail }}

Support: {{ config('shop.support_contact', 'support@khararif.ae') }}
