<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Avatar\DashboardAvatarController;

Route::prefix('dashboard')->middleware('auth:sanctum')->group(function () {
    Route::get('avatars', [DashboardAvatarController::class, 'index']);
    Route::post('avatars', [DashboardAvatarController::class, 'store']);
    Route::get('avatars/{avatar}', [DashboardAvatarController::class, 'show']);
    Route::put('avatars/{avatar}', [DashboardAvatarController::class, 'update']);
    Route::delete('avatars/{avatar}', [DashboardAvatarController::class, 'destroy']);
});
