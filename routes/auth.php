<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Role\PermissionController;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Route::prefix('dashboard')->group(function () {
    Route::post('login', [LoginController::class, 'login']);

    Route::post('check-token', [LoginController::class, 'check_token']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [LoginController::class, 'logout']);

        Route::get('profile', [UserController::class, 'getProfile']);
        Route::post('/change-password', [UserController::class, 'changePassword']);
        Route::post('/change-image', [UserController::class, 'changeImage']);
        Route::post('/change-info', [UserController::class, 'changeInfo']);
    });
});
