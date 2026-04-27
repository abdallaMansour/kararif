<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تفاصيل الطلب</title>
</head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Tahoma,Arial,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;">
                <tr>
                    <td style="background:#1e40af;padding:20px;text-align:center;">
                        @if(!empty($logoUrl))
                            <img src="{{ $logoUrl }}" alt="خراريف" style="max-height:54px;display:block;margin:0 auto 12px auto;">
                        @endif
                        <h2 style="margin:0;color:#ffffff;font-size:22px;">{{ $isAdminRecipient ? 'طلب جديد في المتجر' : 'تم استلام طلبك بنجاح' }}</h2>
                    </td>
                </tr>
                <tr>
                    <td style="padding:22px 24px;">
                        <p style="margin:0 0 8px 0;"><strong>رقم الطلب:</strong> {{ $order->order_number }}</p>
                        <p style="margin:0 0 16px 0;"><strong>حالة الطلب:</strong> طلب جديد</p>
                        <p style="margin:0 0 12px 0;font-size:17px;"><strong>بيانات العميل</strong></p>
                        <p style="margin:0 0 6px 0;">الاسم: {{ $order->customer_full_name }}</p>
                        <p style="margin:0 0 6px 0;">الهاتف: {{ $order->customer_phone }}</p>
                        <p style="margin:0 0 16px 0;">البريد الإلكتروني: {{ $order->customer_email }}</p>
                        <p style="margin:0 0 12px 0;font-size:17px;"><strong>المنتجات</strong></p>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                            @foreach($order->items as $item)
                                <tr>
                                    <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;">{{ $item->product?->name_ar ?? ('منتج #' . $item->shop_product_id) }} × {{ $item->quantity }}</td>
                                    <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;text-align:left;">{{ number_format($item->line_total_aed, 2) }} درهم</td>
                                </tr>
                            @endforeach
                        </table>
                        <p style="margin:14px 0 6px 0;">الإجمالي الفرعي: {{ number_format($order->subtotal_aed, 2) }} درهم</p>
                        <p style="margin:0 0 6px 0;">رسوم الشحن: {{ number_format($order->shipping_fee_aed, 2) }} درهم</p>
                        <p style="margin:0 0 16px 0;"><strong>الإجمالي الكلي: {{ number_format($order->total_aed, 2) }} درهم</strong></p>
                        <p style="margin:0 0 6px 0;font-size:17px;"><strong>عنوان التوصيل</strong></p>
                        <p style="margin:0 0 4px 0;">{{ $order->delivery_emirate }} - {{ $order->delivery_area }}</p>
                        <p style="margin:0 0 14px 0;">{{ $order->delivery_detail }}</p>
                        <p style="margin:0;color:#6b7280;">الدعم: {{ config('shop.support_contact', 'support@khararif.ae') }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
