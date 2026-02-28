<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ziina Payment Gateway
    |--------------------------------------------------------------------------
    | Used for game-sessions purchases. Set ZIINA_ACCESS_TOKEN in .env for production.
    | See: https://docs.ziina.com/developers/custom-integration
    */
    'base_url' => env('ZIINA_BASE_URL', 'https://api.ziina.com'),
    'access_token' => env('ZIINA_ACCESS_TOKEN'),
];
