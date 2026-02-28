<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Coupon\DashboardCouponController;

Route::prefix('dashboard')->middleware(['auth:sanctum', 'hasPermission:coupons'])->group(function () {
    Route::get('coupons', [DashboardCouponController::class, 'index']);
    Route::post('coupons', [DashboardCouponController::class, 'store']);
    Route::get('coupons/{coupon}', [DashboardCouponController::class, 'show']);
    Route::put('coupons/{coupon}', [DashboardCouponController::class, 'update']);
    Route::delete('coupons/{coupon}', [DashboardCouponController::class, 'destroy']);
});
