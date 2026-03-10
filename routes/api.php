<?php

use Illuminate\Support\Facades\Route;

// Public app avatars list (register first so it is never shadowed by dashboard routes)
Route::get('avatars', [\App\Http\Controllers\Avatar\AvatarController::class, 'index']);

// API routes: no locale in path; app uses Arabic (ar) for API
require __DIR__ . '/frontend_api.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/roles.php';
require __DIR__ . '/seo.php';
require __DIR__ . '/contact_us.php';
require __DIR__ . '/admin.php';
require __DIR__ . '/setting.php';
require __DIR__ . '/about_us.php';
require __DIR__ . '/story.php';
require __DIR__ . '/toy.php';
require __DIR__ . '/book_availability.php';
require __DIR__ . '/opinion.php';
require __DIR__ . '/full_story.php';
require __DIR__ . '/questions_stages.php';
require __DIR__ . '/adventurers.php';
require __DIR__ . '/avatars.php';
require __DIR__ . '/packages.php';
require __DIR__ . '/coupons.php';
require __DIR__ . '/ranks.php';
require __DIR__ . '/scoreboard.php';
require __DIR__ . '/news.php';
require __DIR__ . '/how_to_play.php';
