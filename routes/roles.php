<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Role\RoleController;
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
    Route::prefix('roles')->middleware('hasPermission:role')->group(function () {
        Route::get('select', [RoleController::class, 'select']);
        Route::get('/', [RoleController::class, 'index']);
        Route::get('/{role_id}', [RoleController::class, 'show']);
        Route::post('/', [RoleController::class, 'store']);
        Route::post('user-permission', [PermissionController::class, 'user_permission']);
        Route::post('/{roleId}', [RoleController::class, 'update']);
        Route::delete('/{roleId}', [RoleController::class, 'destroy']);
    });

    Route::prefix('permissions')->group(function () {
        Route::get('select', [PermissionController::class, 'select']);
        Route::get('/', [PermissionController::class, 'index']);
        Route::post('/assign/{roleId}', [PermissionController::class, 'assign']);
    });
});
