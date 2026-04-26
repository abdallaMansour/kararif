<?php

$rawOrigins = array_values(array_filter([
    env('NEXT_PUBLIC_BASE_URL'),
    env('FRONTEND_BASE_URL'),
]));

$normalizedOrigins = [];

foreach ($rawOrigins as $originEntry) {
    foreach (explode(',', (string) $originEntry) as $origin) {
        $origin = trim($origin);
        if ($origin === '') {
            continue;
        }
        if ($origin !== '*') {
            $origin = rtrim($origin, '/');
        }
        $normalizedOrigins[] = $origin;
    }
}

$normalizedOrigins = array_values(array_unique($normalizedOrigins));
if ($normalizedOrigins === []) {
    $normalizedOrigins = ['*'];
}

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $normalizedOrigins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
