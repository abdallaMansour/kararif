<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactUs\ContactUsController;
use App\Http\Controllers\ContactUs\DashboardContactUsController;

Route::post('contact-us', [ContactUsController::class, 'create']);

Route::get('read-all-contact-us', [DashboardContactUsController::class, 'readAllContactUs']);

Route::prefix('dashboard')->middleware('hasPermission:contact_us')->group(function () {
    Route::get('contact-us', [DashboardContactUsController::class, 'index']);
    Route::get('contact-us/{contact_us_id}', [DashboardContactUsController::class, 'show']);
    Route::delete('contact-us/{contact_us_id}', [DashboardContactUsController::class, 'destroy']);
});
