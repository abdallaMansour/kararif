<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuestionsStages\StageController;
use App\Http\Controllers\QuestionsStages\DashboardStageController;
use App\Http\Controllers\QuestionsStages\CategoryController;
use App\Http\Controllers\QuestionsStages\DashboardCategoryController;
use App\Http\Controllers\QuestionsStages\SubcategoryController;
use App\Http\Controllers\QuestionsStages\DashboardSubcategoryController;
use App\Http\Controllers\QuestionsStages\TypeController;
use App\Http\Controllers\QuestionsStages\DashboardTypeController;
use App\Http\Controllers\QuestionsStages\QuestionController;
use App\Http\Controllers\QuestionsStages\DashboardQuestionController;

// Public (website) routes
Route::get('stages', [StageController::class, 'index']);
Route::get('stages/{stage}', [StageController::class, 'show']);
Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{category}', [CategoryController::class, 'show']);
Route::get('subcategories', [SubcategoryController::class, 'index']);
Route::get('subcategories/{subcategory}', [SubcategoryController::class, 'show']);
Route::get('types', [TypeController::class, 'index']);
Route::get('types/{type}', [TypeController::class, 'show']);
Route::get('questions', [QuestionController::class, 'index']);
Route::get('questions/{question}', [QuestionController::class, 'show']);

// Dashboard routes: stages, categories, subcategories, types (questions_and_stages permission)
Route::prefix('dashboard')->middleware('hasPermission:questions_and_stages')->group(function () {
    Route::get('stages', [DashboardStageController::class, 'index']);
    Route::post('stages', [DashboardStageController::class, 'create']);
    Route::get('stages/{stage}', [DashboardStageController::class, 'show']);
    Route::post('stages/{stage}', [DashboardStageController::class, 'update']);
    Route::delete('stages/{stage}', [DashboardStageController::class, 'destroy']);
    Route::get('categories', [DashboardCategoryController::class, 'index']);
    Route::post('categories', [DashboardCategoryController::class, 'create']);
    Route::get('categories/{category}', [DashboardCategoryController::class, 'show']);
    Route::post('categories/{category}', [DashboardCategoryController::class, 'update']);
    Route::delete('categories/{category}', [DashboardCategoryController::class, 'destroy']);
    Route::get('subcategories', [DashboardSubcategoryController::class, 'index']);
    Route::post('subcategories', [DashboardSubcategoryController::class, 'create']);
    Route::get('subcategories/{subcategory}', [DashboardSubcategoryController::class, 'show']);
    Route::post('subcategories/{subcategory}', [DashboardSubcategoryController::class, 'update']);
    Route::delete('subcategories/{subcategory}', [DashboardSubcategoryController::class, 'destroy']);
    Route::get('types', [DashboardTypeController::class, 'index']);
    Route::post('types', [DashboardTypeController::class, 'create']);
    Route::get('types/{type}', [DashboardTypeController::class, 'show']);
    Route::post('types/{type}', [DashboardTypeController::class, 'update']);
    Route::delete('types/{type}', [DashboardTypeController::class, 'destroy']);
});

// Dashboard routes: questions only (questions permission)
Route::prefix('dashboard')->middleware('hasPermission:questions')->group(function () {
    Route::get('questions', [DashboardQuestionController::class, 'index']);
    Route::post('questions', [DashboardQuestionController::class, 'create']);
    Route::get('questions/{question}', [DashboardQuestionController::class, 'show']);
    Route::post('questions/{question}', [DashboardQuestionController::class, 'update']);
    Route::delete('questions/{question}', [DashboardQuestionController::class, 'destroy']);
});
