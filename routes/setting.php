<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Setting\SettingController;
use App\Http\Controllers\Setting\DashboardSettingController;

Route::get('setting', [SettingController::class, 'index']);
Route::get('setting/logo', [SettingController::class, 'logo']);
Route::get('terms', [SettingController::class, 'terms']);
Route::get('privacy-policy', [SettingController::class, 'privacyPolicy']);

Route::prefix('dashboard')->middleware('hasPermission:setting')->group(function () {
    Route::get('setting', [DashboardSettingController::class, 'index']);
    Route::post('setting', [DashboardSettingController::class, 'update']);
    Route::get('terms', [DashboardSettingController::class, 'showTerms']);
    Route::post('terms', [DashboardSettingController::class, 'updateTerms']);
    Route::get('privacy-policy', [DashboardSettingController::class, 'showPrivacy']);
    Route::post('privacy-policy', [DashboardSettingController::class, 'updatePrivacy']);
    Route::get('faq', [DashboardSettingController::class, 'showFaq']);
    Route::post('faq', [DashboardSettingController::class, 'updateFaq']);
});
