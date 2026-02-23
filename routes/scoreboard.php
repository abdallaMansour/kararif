<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Scoreboard\ScoreboardController;

Route::get('scoreboard', [ScoreboardController::class, 'index']);
