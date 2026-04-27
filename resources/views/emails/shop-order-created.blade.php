<h2>{{ $isAdminRecipient ? 'New order received' : 'Your order has been received' }}</h2>
<p>Order Number: <strong>{{ $order->order_number }}</strong></p>
<p>Current Status: <strong>{{ ucfirst(str_replace('_', ' ', $order->status)) }}</strong></p>

<h3>Customer</h3>
<p>Name: {{ $order->customer_full_name }}</p>
<p>Phone: {{ $order->customer_phone }}</p>
<p>Email: {{ $order->customer_email }}</p>

<h3>Items</h3>
<ul>
@foreach($order->items as $item)
    <li>{{ $item->product?->name_ar ?? 'Product #' . $item->shop_product_id }} x {{ $item->quantity }} - {{ number_format($item->line_total_aed, 2) }} AED</li>
@endforeach
</ul>

<p>Subtotal: {{ number_format($order->subtotal_aed, 2) }} AED</p>
<p>Shipping: {{ number_format($order->shipping_fee_aed, 2) }} AED</p>
<p><strong>Total: {{ number_format($order->total_aed, 2) }} AED</strong></p>

<h3>Delivery</h3>
<p>{{ $order->delivery_emirate }} - {{ $order->delivery_area }}</p>
<p>{{ $order->delivery_detail }}</p>

<p>Support: {{ config('shop.support_contact', 'support@khararif.ae') }}</p>
