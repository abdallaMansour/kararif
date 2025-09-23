<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Toy\DashboardToyController;

Route::get('toy', [DashboardToyController::class, 'index']);
Route::get('toy/{toy}', [DashboardToyController::class, 'show']);

Route::prefix('dashboard/toy')->group(function () {
    Route::get('/', [DashboardToyController::class, 'index']);
    Route::post('/', [DashboardToyController::class, 'create']);
    Route::get('/{toy}', [DashboardToyController::class, 'show']);
    Route::post('/{toy}', [DashboardToyController::class, 'update']);
    Route::delete('/{toy}', [DashboardToyController::class, 'destroy']);
});
