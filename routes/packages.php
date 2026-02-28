<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Package\DashboardPackageController;

Route::prefix('dashboard')->middleware(['auth:sanctum', 'hasPermission:packages'])->group(function () {
    Route::get('packages', [DashboardPackageController::class, 'index']);
    Route::post('packages', [DashboardPackageController::class, 'store']);
    Route::get('packages/{payment_package}', [DashboardPackageController::class, 'show']);
    Route::put('packages/{payment_package}', [DashboardPackageController::class, 'update']);
    Route::delete('packages/{payment_package}', [DashboardPackageController::class, 'destroy']);
});
