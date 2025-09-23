<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardAdminController;

Route::prefix('dashboard')->middleware('hasPermission:admin')->group(function () {
    Route::get('admin', [DashboardAdminController::class, 'index']);
    Route::get('admin/{admin_id}', [DashboardAdminController::class, 'show']);
    Route::post('admin', [DashboardAdminController::class, 'create']);
    Route::post('admin/{admin_id}', [DashboardAdminController::class, 'update']);
    Route::delete('admin/{admin_id}', [DashboardAdminController::class, 'destroy']);
});
