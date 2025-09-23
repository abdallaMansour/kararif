<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Setting\SettingController;
use App\Http\Controllers\Setting\DashboardSettingController;

Route::get('setting', [SettingController::class, 'index']);

Route::get('setting/logo', [SettingController::class, 'logo']);

Route::prefix('dashboard')->middleware('hasPermission:setting')->group(function () {
    Route::get('setting', [DashboardSettingController::class, 'index']);
    Route::post('setting', [DashboardSettingController::class, 'update']);
});
