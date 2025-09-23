<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AboutUs\DashboardAboutUsController;

Route::get('about_us', [DashboardAboutUsController::class, 'web_index']);

Route::prefix('dashboard/about_us')->middleware('hasPermission:about_us')->group(function () {
    Route::get('/', [DashboardAboutUsController::class, 'index']);
    Route::post('/', [DashboardAboutUsController::class, 'update']);
});
