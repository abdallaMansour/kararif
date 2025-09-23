<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookAvailability\DashboardBookAvailabilityController;

Route::get('book-availability', [DashboardBookAvailabilityController::class, 'index']);
Route::get('book-availability/{bookAvailability}', [DashboardBookAvailabilityController::class, 'show']);

Route::prefix('dashboard/book-availability')->group(function () {
    Route::get('/', [DashboardBookAvailabilityController::class, 'index']);
    Route::post('/', [DashboardBookAvailabilityController::class, 'create']);
    Route::get('/{bookAvailability}', [DashboardBookAvailabilityController::class, 'show']);
    Route::post('/{bookAvailability}', [DashboardBookAvailabilityController::class, 'update']);
    Route::delete('/{bookAvailability}', [DashboardBookAvailabilityController::class, 'destroy']);
});
