<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Adventurer\DashboardAdventurerController;

Route::prefix('dashboard')->middleware('hasPermission:adventurers')->group(function () {
    Route::get('adventurers', [DashboardAdventurerController::class, 'index']);
    Route::post('adventurers', [DashboardAdventurerController::class, 'create']);
    Route::get('adventurers/{adventurer}', [DashboardAdventurerController::class, 'show']);
    Route::post('adventurers/{adventurer}', [DashboardAdventurerController::class, 'update']);
    Route::delete('adventurers/{adventurer}', [DashboardAdventurerController::class, 'destroy']);
});
