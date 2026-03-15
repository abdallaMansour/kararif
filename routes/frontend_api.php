<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\AppLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Game\GameController;
use App\Http\Controllers\Game\TvDisplayController;

// Auth (frontend/app paths)
Route::prefix('auth')->group(function () {
    // App/adventurer login: ApiResponse shape
    Route::post('login', [AppLoginController::class, 'login']);
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
    Route::patch('avatar', [UserController::class, 'assignAvatar']);
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
Route::post('payment/webhook', [\App\Http\Controllers\PaymentWebhookController::class, 'ziina']);

Route::post('coupons/apply', [\App\Http\Controllers\Coupon\CouponController::class, 'apply'])->middleware('auth:sanctum');

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

    // TV display (no auth)
    Route::post('tv/code', [TvDisplayController::class, 'getOrCreateCode']);
    Route::get('tv/display/by-code/{code}', [TvDisplayController::class, 'getDisplayStatusByCode']);
    Route::get('tv/display/{displayId}', [TvDisplayController::class, 'getDisplayStatus']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('create-room', [GameController::class, 'createRoom']);
        Route::post('room/{roomId}/link-tv', [GameController::class, 'linkTv']);
        Route::post('room/{roomId}/join', [GameController::class, 'joinRoom']);
        Route::post('room/{roomId}/leave', [GameController::class, 'leaveRoom']);
        Route::post('session/{sessionId}/answer', [GameController::class, 'submitAnswer']);
        Route::post('session/{sessionId}/next-question', [GameController::class, 'nextQuestion']);
        Route::post('session/{sessionId}/surrender', [GameController::class, 'surrender']);
    });
});
