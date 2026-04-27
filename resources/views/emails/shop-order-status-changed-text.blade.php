تحديث حالة الطلب
رقم الطلب: {{ $order->order_number }}
الحالة السابقة: {{ $previousStatusLabel }}
الحالة الحالية: {{ $currentStatusLabel }}

المنتجات:
@foreach($order->items as $item)
- {{ $item->product?->name_ar ?? ('منتج #' . $item->shop_product_id) }} - {{ number_format($item->line_total_aed, 2) }} درهم
  الكمية: {{ (int) $item->quantity }}
@if(!empty($item->signature_names))
  أسماء الإهداء: {{ collect($item->signature_names)->implode('، ') }}
@endif
@endforeach

الإجمالي الفرعي: {{ number_format($order->subtotal_aed, 2) }} درهم
رسوم الشحن: {{ number_format($order->shipping_fee_aed, 2) }} درهم
الإجمالي الكلي: {{ number_format($order->total_aed, 2) }} درهم
عنوان التوصيل: {{ $order->delivery_emirate }} - {{ $order->delivery_area }}
{{ $order->delivery_detail }}

الدعم: {{ config('shop.support_contact', 'support@khararif.ae') }}
