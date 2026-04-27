Order status updated
Order Number: {{ $order->order_number }}
Previous Status: {{ $previousStatusLabel() }}
Current Status: {{ $currentStatusLabel() }}

Items:
@foreach($order->items as $item)
- {{ $item->product?->name_ar ?? 'Product #' . $item->shop_product_id }} x {{ $item->quantity }} - {{ number_format($item->line_total_aed, 2) }} AED
@endforeach

Total: {{ number_format($order->total_aed, 2) }} AED
Delivery: {{ $order->delivery_emirate }} - {{ $order->delivery_area }}
{{ $order->delivery_detail }}

Support: {{ config('shop.support_contact', 'support@khararif.ae') }}
