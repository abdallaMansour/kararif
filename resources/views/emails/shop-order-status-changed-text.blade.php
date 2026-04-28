تحديث حالة الطلب
رقم الطلب: {{ $order->order_number }}
الحالة السابقة: {{ $previousStatusLabel }}
الحالة الحالية: {{ $currentStatusLabel }}

المنتجات:
@foreach($order->items as $item)
- {{ $item->product?->name_ar ?? ('منتج #' . $item->shop_product_id) }} - {{ number_format($item->line_total_aed, 2) }} د.إ
  الكمية: {{ (int) $item->quantity }}
@if(!empty($item->signature_names))
  أسماء الإهداء: {{ collect($item->signature_names)->implode('، ') }}
@endif
@endforeach

الإجمالي الفرعي: {{ number_format($order->subtotal_aed, 2) }} د.إ
رسوم الشحن: {{ number_format($order->shipping_fee_aed, 2) }} د.إ
الإجمالي الكلي: {{ number_format($order->total_aed, 2) }} د.إ
عنوان التوصيل: {{ (['abu_dhabi' => 'أبوظبي','abu dhabi' => 'أبوظبي','dubai' => 'دبي','sharjah' => 'الشارقة','ajman' => 'عجمان','umm_al_quwain' => 'أم القيوين','umm al quwain' => 'أم القيوين','ras_al_khaimah' => 'رأس الخيمة','ras al khaimah' => 'رأس الخيمة','fujairah' => 'الفجيرة'][strtolower(trim((string)$order->delivery_emirate))] ?? $order->delivery_emirate) }} - {{ $order->delivery_area }}
{{ $order->delivery_detail }}

الدعم: contact@evorq.com
