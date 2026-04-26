<?php

use App\Http\Controllers\Shop\ShopOrderController;
use App\Http\Controllers\Shop\ShopPaymentWebhookController;
use App\Http\Controllers\Shop\ShopProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('shop')->group(function () {
    Route::get('products', [ShopProductController::class, 'index']);
    Route::post('checkout', [ShopOrderController::class, 'checkout'])->middleware('throttle:shop-checkout');
    Route::get('orders/{orderId}', [ShopOrderController::class, 'show']);
    Route::post('payment/webhook', [ShopPaymentWebhookController::class, 'ziina']);
});
