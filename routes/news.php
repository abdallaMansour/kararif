<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\News\DashboardNewsController;

// Public GET news is in frontend_api.php

Route::prefix('dashboard')->middleware(['auth:sanctum', 'hasPermission:setting'])->group(function () {
    Route::get('news', [DashboardNewsController::class, 'index']);
    Route::post('news', [DashboardNewsController::class, 'store']);
    Route::get('news/{news}', [DashboardNewsController::class, 'show']);
    Route::put('news/{news}', [DashboardNewsController::class, 'update']);
    Route::patch('news/{news}', [DashboardNewsController::class, 'update']);
    Route::delete('news/{news}', [DashboardNewsController::class, 'destroy']);
});
