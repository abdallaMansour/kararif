<?php

return [
    'shipping_fee_aed' => (float) env('SHOP_SHIPPING_FEE_AED', 30),
    'frontend_confirmation_url' => env('SHOP_FRONTEND_CONFIRMATION_URL', 'https://<frontend-domain>/order/confirmation'),
    'frontend_cancel_url' => env('SHOP_FRONTEND_CANCEL_URL'),
    'confirmation_token_ttl_minutes' => (int) env('SHOP_CONFIRMATION_TOKEN_TTL_MINUTES', 60),
    'support_contact' => env('SHOP_SUPPORT_CONTACT', 'support@khararif.ae'),
];
