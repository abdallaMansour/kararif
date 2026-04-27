تحديث حالة الطلب
رقم الطلب: {{ $order->order_number }}
الحالة السابقة: {{ $previousStatusLabel }}
الحالة الحالية: {{ $currentStatusLabel }}

المنتجات:
@foreach($order->items as $item)
- {{ $item->product?->name_ar ?? ('منتج #' . $item->shop_product_id) }} × {{ $item->quantity }} - {{ number_format($item->line_total_aed, 2) }} درهم
@endforeach

الإجمالي الكلي: {{ number_format($order->total_aed, 2) }} درهم
عنوان التوصيل: {{ $order->delivery_emirate }} - {{ $order->delivery_area }}
{{ $order->delivery_detail }}

الدعم: {{ config('shop.support_contact', 'support@khararif.ae') }}
