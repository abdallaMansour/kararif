<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Opinion\DashboardOpinionController;

Route::get('opinion', [DashboardOpinionController::class, 'index']);
Route::get('opinion/{opinion}', [DashboardOpinionController::class, 'show']);

Route::prefix('dashboard/opinion')->group(function () {
    Route::get('/', [DashboardOpinionController::class, 'index']);
    Route::post('/', [DashboardOpinionController::class, 'create']);
    Route::get('/{opinion}', [DashboardOpinionController::class, 'show']);
    Route::post('/{opinion}', [DashboardOpinionController::class, 'update']);
    Route::delete('/{opinion}', [DashboardOpinionController::class, 'destroy']);
});
