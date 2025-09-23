<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Seo\SeoController;
use App\Http\Controllers\Seo\DashboardSeoController;

Route::get('seo/{page}', [SeoController::class, 'show']);

Route::prefix('dashboard/seo')->middleware('hasPermission:seo')->group(function () {
    Route::get('all', [DashboardSeoController::class, 'index']);
    Route::get('{page}', [DashboardSeoController::class, 'show']);
    Route::post('{page}', [DashboardSeoController::class, 'update']);
});
