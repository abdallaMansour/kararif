<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Story\DashboardStoryController;

Route::get('story', [DashboardStoryController::class, 'index']);
Route::get('story/{story}', [DashboardStoryController::class, 'show']);

Route::prefix('dashboard/story')->group(function () {
    Route::get('/', [DashboardStoryController::class, 'index']);
    Route::post('/', [DashboardStoryController::class, 'create']);
    Route::get('/{story}', [DashboardStoryController::class, 'show']);
    Route::post('/{story}', [DashboardStoryController::class, 'update']);
    Route::delete('/{story}', [DashboardStoryController::class, 'destroy']);
});
