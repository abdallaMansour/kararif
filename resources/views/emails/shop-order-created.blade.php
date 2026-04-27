<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تفاصيل الطلب</title>
</head>
<body style="margin:0;padding:0;background:#d8d5c9;font-family:Tahoma,Arial,sans-serif;color:#4b3f2f;direction:rtl;text-align:right;" dir="rtl">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;background:#fefefe;border-radius:18px;overflow:hidden;border:1px solid #99c8cf;direction:rtl;text-align:right;" dir="rtl">
                <tr>
                    <td style="background:linear-gradient(180deg,#8fd5e4 0%,#b7e7ef 48%,#f4f9fb 100%);padding:22px;text-align:center;">
                        <p style="margin:0 0 8px 0;color:#174f59;font-size:36px;line-height:1.1;font-weight:900;letter-spacing:0.4px;">خراريف</p>
                        @php
                            $cidLogoPath = public_path('animated-logo-ii.gif');
                            $embeddedLogo = isset($message) && file_exists($cidLogoPath)
                                ? $message->embed($cidLogoPath)
                                : null;
                        @endphp
                        @if(!empty($embeddedLogo))
                            <img src="{{ $embeddedLogo }}" alt="خراريف" style="max-height:92px;display:block;margin:0 auto 12px auto;">
                        @elseif(!empty($logoUrl))
                            <img src="{{ $logoUrl }}" alt="خراريف" style="max-height:92px;display:block;margin:0 auto 12px auto;">
                        @endif
                        <h2 style="margin:0;color:#2e5f68;font-size:24px;">{{ $isAdminRecipient ? 'طلب جديد في المتجر' : 'تم استلام طلبك بنجاح' }}</h2>
                    </td>
                </tr>
                <tr>
                    <td style="padding:22px 24px;direction:rtl;text-align:right;">
                        <p style="margin:0 0 8px 0;"><strong>رقم الطلب:</strong> <span style="color:#8b3f2b;">{{ $order->order_number }}</span></p>
                        <p style="margin:0 0 16px 0;"><strong>حالة الطلب:</strong> طلب جديد</p>
                        <p style="margin:0 0 12px 0;font-size:17px;color:#2e5f68;"><strong>بيانات العميل</strong></p>
                        <p style="margin:0 0 6px 0;">الاسم: {{ $order->customer_full_name }}</p>
                        <p style="margin:0 0 6px 0;">الهاتف: {{ $order->customer_phone }}</p>
                        <p style="margin:0 0 16px 0;">البريد الإلكتروني: {{ $order->customer_email }}</p>
                        <p style="margin:0 0 12px 0;font-size:17px;color:#2e5f68;"><strong>المنتجات</strong></p>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;direction:rtl;text-align:right;" dir="rtl">
                            @foreach($order->items as $item)
                                <tr>
                                    <td style="padding:8px 0;border-bottom:1px solid #d3e6ea;">
                                        <div style="font-weight:700;">{{ $item->product?->name_ar ?? ('منتج #' . $item->shop_product_id) }}</div>
                                        <div style="margin-top:4px;color:#6e675b;font-size:13px;">
                                            الكمية: {{ (int) $item->quantity }}
                                        </div>
                                        @if(!empty($item->signature_names))
                                            <div style="margin-top:6px;color:#6e675b;font-size:13px;">
                                                أسماء الإهداء:
                                                {{ collect($item->signature_names)->implode('، ') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td style="padding:8px 0;border-bottom:1px solid #d3e6ea;text-align:left;">{{ number_format($item->line_total_aed, 2) }} درهم</td>
                                </tr>
                            @endforeach
                        </table>
                        <p style="margin:14px 0 6px 0;">الإجمالي الفرعي: <strong>{{ number_format($order->subtotal_aed, 2) }} درهم</strong></p>
                        <p style="margin:0 0 6px 0;">رسوم الشحن: {{ number_format($order->shipping_fee_aed, 2) }} درهم</p>
                        <p style="margin:0 0 16px 0;color:#8b3f2b;"><strong>الإجمالي الكلي: {{ number_format($order->total_aed, 2) }} درهم</strong></p>
                        <p style="margin:0 0 6px 0;font-size:17px;color:#2e5f68;"><strong>عنوان التوصيل</strong></p>
                        <p style="margin:0 0 4px 0;">{{ $order->delivery_emirate }} - {{ $order->delivery_area }}</p>
                        <p style="margin:0 0 14px 0;">{{ $order->delivery_detail }}</p>
                        <p style="margin:0;color:#6e675b;">الدعم: {{ config('shop.support_contact', 'support@khararif.ae') }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
