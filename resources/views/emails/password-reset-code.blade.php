<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Password Reset</title>
</head>
<body>
    <p>{{ __('Your password reset code is:') }}</p>
    <h2>{{ $code }}</h2>
    <p>{{ __('This code expires in 15 minutes.') }}</p>
</body>
</html>
