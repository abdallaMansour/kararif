<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FullStory\DashboardFullStoryController;

Route::get('full-story', [DashboardFullStoryController::class, 'index']);
Route::get('full-story/{full_story}', [DashboardFullStoryController::class, 'show']);

Route::prefix('dashboard/full-story')->group(function () {
    Route::get('/', [DashboardFullStoryController::class, 'index']);
    Route::post('/', [DashboardFullStoryController::class, 'create']);
    Route::get('/{full_story}', [DashboardFullStoryController::class, 'show']);
    Route::post('/{full_story}', [DashboardFullStoryController::class, 'update']);
    Route::delete('/{full_story}', [DashboardFullStoryController::class, 'destroy']);
});
