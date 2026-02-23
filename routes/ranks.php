<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Rank\RankController;
use App\Http\Controllers\Rank\DashboardRankController;

Route::get('ranks', [RankController::class, 'index']);

Route::prefix('dashboard')->middleware('hasPermission:ranks')->group(function () {
    Route::get('ranks', [DashboardRankController::class, 'index']);
    Route::post('ranks', [DashboardRankController::class, 'create']);
    Route::get('ranks/{rank}', [DashboardRankController::class, 'show']);
    Route::post('ranks/{rank}', [DashboardRankController::class, 'update']);
    Route::delete('ranks/{rank}', [DashboardRankController::class, 'destroy']);
});
