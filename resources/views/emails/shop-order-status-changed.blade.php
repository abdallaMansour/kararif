<h2>Order status updated</h2>
<p>Your order <strong>{{ $order->order_number }}</strong> has a new status.</p>
<p>Previous Status: <strong>{{ $previousStatusLabel() }}</strong></p>
<p>Current Status: <strong>{{ $currentStatusLabel() }}</strong></p>

<h3>Items</h3>
<ul>
@foreach($order->items as $item)
    <li>{{ $item->product?->name_ar ?? 'Product #' . $item->shop_product_id }} x {{ $item->quantity }} - {{ number_format($item->line_total_aed, 2) }} AED</li>
@endforeach
</ul>

<p><strong>Total: {{ number_format($order->total_aed, 2) }} AED</strong></p>
<p>Delivery: {{ $order->delivery_emirate }} - {{ $order->delivery_area }}</p>
<p>{{ $order->delivery_detail }}</p>

<p>Support: {{ config('shop.support_contact', 'support@khararif.ae') }}</p>
