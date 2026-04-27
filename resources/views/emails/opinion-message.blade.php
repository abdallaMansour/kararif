<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>رأي جديد</title>
</head>
<body style="margin:0;padding:0;background:#d8d5c9;font-family:Tahoma,Arial,sans-serif;color:#4b3f2f;direction:rtl;text-align:right;" dir="rtl">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;background:#fefefe;border-radius:18px;overflow:hidden;border:1px solid #99c8cf;direction:rtl;text-align:right;" dir="rtl">
                <tr>
                    <td style="background:linear-gradient(180deg,#8fd5e4 0%,#b7e7ef 48%,#f4f9fb 100%);padding:22px;text-align:center;">
                        <p style="margin:0 0 8px 0;color:#174f59;font-size:36px;line-height:1.1;font-weight:900;">خراريف</p>
                        @php
                            $cidLogoPath = public_path('animated-logo-ii.gif');
                            $embeddedLogo = isset($message) && file_exists($cidLogoPath) ? $message->embed($cidLogoPath) : null;
                        @endphp
                        @if(!empty($embeddedLogo))
                            <img src="{{ $embeddedLogo }}" alt="خراريف" style="max-height:92px;display:block;margin:0 auto 12px auto;">
                        @endif
                        <h2 style="margin:0;color:#2e5f68;font-size:24px;">رأي جديد من العملاء</h2>
                    </td>
                </tr>
                <tr>
                    <td style="padding:22px 24px;">
                        <p style="margin:0 0 8px 0;"><strong>الاسم:</strong> {{ $opinion->name }}</p>
                        @if($opinion->email)
                            <p style="margin:0 0 8px 0;"><strong>البريد:</strong> {{ $opinion->email }}</p>
                        @endif
                        @if($opinion->phone)
                            <p style="margin:0 0 8px 0;"><strong>الهاتف:</strong> {{ $opinion->phone }}</p>
                        @endif
                        <p style="margin:0 0 8px 0;"><strong>التقييم:</strong> {{ $opinion->rate }}/5</p>
                        <p style="margin:0 0 8px 0;"><strong>الرأي:</strong></p>
                        <p style="margin:0;line-height:1.7;">{{ $opinion->opinion }}</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
