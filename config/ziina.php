<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ziina Payment Gateway
    |--------------------------------------------------------------------------
    | Game-sessions purchases. See: https://docs.ziina.com/developers/custom-integration
    */
    'api_base' => env('ZIINA_API_BASE', 'https://api-v2.ziina.com/api'),
    'api_key' => env('ZIINA_API_KEY'),
    'success_url' => env('ZIINA_SUCCESS_URL', 'https://yourapp.com/payments/success'),
    'cancel_url' => env('ZIINA_CANCEL_URL', 'https://yourapp.com/payments/cancel'),
    'currency' => env('ZIINA_CURRENCY', 'AED'),
    'mode' => env('ZIINA_MODE', 'sandbox'),
    'user_agent' => env('ZIINA_USER_AGENT', 'Khararif-Backend/1.0'),
];
