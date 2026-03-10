<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>رسالة تواصل جديدة</title>
</head>
<body>
    <h2>رسالة تواصل جديدة</h2>
    <p><strong>الاسم:</strong> {{ $contact->name }}</p>
    <p><strong>البريد:</strong> {{ $contact->email }}</p>
    <p><strong>المصدر:</strong> {{ $sourceLabel ?? $contact->source ?? '—' }}</p>
    @if($contact->category)
    <p><strong>الفئة:</strong> {{ $contact->category }}</p>
    @endif
    @if($contact->subject)
    <p><strong>الموضوع:</strong> {{ $contact->subject }}</p>
    @endif
    <p><strong>الرسالة:</strong></p>
    <p>{{ $contact->message }}</p>
</body>
</html>
