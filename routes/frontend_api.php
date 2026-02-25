<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Game\GameController;

// Auth (frontend paths)
Route::prefix('auth')->group(function () {
    Route::post('login', [LoginController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('verify-reset-code', [AuthController::class, 'verifyResetCode']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [LoginController::class, 'logout']);
    });
});

// User (profile, balance, account)
Route::prefix('user')->middleware('auth:sanctum')->group(function () {
    Route::get('profile', [UserController::class, 'getProfile']);
    Route::patch('profile', [UserController::class, 'updateProfile']);
    Route::delete('account', [UserController::class, 'deleteAccount']);
    Route::get('balance', [UserController::class, 'getBalance']);
    Route::get('games', [UserController::class, 'getGames']);
    Route::get('levels', [UserController::class, 'getLevels']);
});

// Contact (frontend may call /contact; contact-us also in contact_us.php)
Route::post('contact', [\App\Http\Controllers\ContactUs\ContactUsController::class, 'create']);
// Support
Route::post('support/tickets', [\App\Http\Controllers\SupportTicketController::class, 'store']);

// Subscription & Payment
Route::post('subscribe', [\App\Http\Controllers\SubscriberController::class, 'store']);
Route::get('payment/packages', [\App\Http\Controllers\PaymentController::class, 'packages']);
Route::post('payment/initiate', [\App\Http\Controllers\PaymentController::class, 'initiate'])->middleware('auth:sanctum');

// Content
Route::get('news', [\App\Http\Controllers\NewsController::class, 'index']);
Route::get('faq', [\App\Http\Controllers\FaqController::class, 'index']);
Route::get('content/how-to-play', [\App\Http\Controllers\ContentController::class, 'howToPlay']);

// Game
Route::prefix('game')->group(function () {
    Route::get('question-types', [GameController::class, 'getQuestionTypes']);
    Route::get('categories', [GameController::class, 'getCategories']);
    Route::get('subcategories', [GameController::class, 'getSubcategories']);
    Route::post('validate-code', [GameController::class, 'validateCode']);
    Route::get('room/{roomId}', [GameController::class, 'getRoom']);
    Route::get('session/{sessionId}', [GameController::class, 'getSession']);
    Route::get('session/{sessionId}/result', [GameController::class, 'getResult']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('create-room', [GameController::class, 'createRoom']);
        Route::post('room/{roomId}/join', [GameController::class, 'joinRoom']);
        Route::post('session/{sessionId}/answer', [GameController::class, 'submitAnswer']);
    });
});
