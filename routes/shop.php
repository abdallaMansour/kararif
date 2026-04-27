<?php

use App\Http\Controllers\Shop\ShopOrderController;
use App\Http\Controllers\Shop\ShopPaymentWebhookController;
use App\Http\Controllers\Shop\ShopProductController;
use App\Http\Controllers\Shop\DashboardShopOrderController;
use App\Http\Controllers\Shop\DashboardShopProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('shop')->group(function () {
    Route::get('products', [ShopProductController::class, 'index']);
    Route::post('checkout', [ShopOrderController::class, 'checkout'])->middleware('throttle:shop-checkout');
    Route::get('orders/{orderId}', [ShopOrderController::class, 'show']);
    Route::post('payment/confirm-success', [ShopOrderController::class, 'confirmPaymentSuccess']);
    Route::post('payment/webhook', [ShopPaymentWebhookController::class, 'ziina']);
});

Route::prefix('dashboard/shop')->group(function () {
    Route::prefix('products')->middleware('hasPermission:shop_products')->group(function () {
        Route::get('/', [DashboardShopProductController::class, 'index']);
        Route::post('/', [DashboardShopProductController::class, 'create']);
        Route::get('/{product}', [DashboardShopProductController::class, 'show']);
        Route::post('/{product}', [DashboardShopProductController::class, 'update']);
        Route::delete('/{product}', [DashboardShopProductController::class, 'destroy']);
    });

    Route::prefix('orders')->middleware('hasPermission:shop_orders')->group(function () {
        Route::get('/', [DashboardShopOrderController::class, 'index']);
        Route::get('/{order}', [DashboardShopOrderController::class, 'show']);
    });

    Route::post('orders/{order}/status', [DashboardShopOrderController::class, 'updateStatus'])
        ->middleware('hasPermission:shop_orders_update_status');
});
