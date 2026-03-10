<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Content\DashboardHowToPlaySectionController;

// Public GET content/how-to-play is in frontend_api.php

Route::prefix('dashboard')->middleware(['auth:sanctum', 'hasPermission:setting'])->group(function () {
    Route::get('how-to-play-sections', [DashboardHowToPlaySectionController::class, 'index']);
    Route::post('how-to-play-sections', [DashboardHowToPlaySectionController::class, 'store']);
    Route::get('how-to-play-sections/{howToPlaySection}', [DashboardHowToPlaySectionController::class, 'show']);
    Route::put('how-to-play-sections/{howToPlaySection}', [DashboardHowToPlaySectionController::class, 'update']);
    Route::patch('how-to-play-sections/{howToPlaySection}', [DashboardHowToPlaySectionController::class, 'update']);
    Route::delete('how-to-play-sections/{howToPlaySection}', [DashboardHowToPlaySectionController::class, 'destroy']);
});
