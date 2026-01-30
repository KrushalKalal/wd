<?php

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiStoreController;
use App\Http\Controllers\Api\ApiVisitController;
use App\Http\Controllers\Api\ApiSurveyController;
use App\Http\Controllers\Api\ApiStockController;
use App\Http\Controllers\Api\ApiTargetController;

// Authentication
Route::post('/login', [ApiAuthController::class, 'login']);
Route::post('/logout', [ApiAuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // Employee Profile
    Route::get('/profile', [ApiAuthController::class, 'profile']);

    // Assigned Stores
    Route::get('/stores', [ApiStoreController::class, 'getAssignedStores']);
    Route::get('/stores/{id}', [ApiStoreController::class, 'getStoreDetails']);
    Route::get('/stores/{id}/products', [ApiStoreController::class, 'getStoreProducts']);

    // Store Visit
    Route::post('/visits/check-in', [ApiVisitController::class, 'checkIn']);
    Route::post('/visits/{id}/check-out', [ApiVisitController::class, 'checkOut']);
    Route::get('/visits/today', [ApiVisitController::class, 'getTodayVisit']);

    // Survey Questions
    Route::get('/questions', [ApiSurveyController::class, 'getActiveQuestions']);
    Route::post('/visits/{visitId}/answers', [ApiSurveyController::class, 'submitAnswers']);

    // Stock Transactions
    Route::post('/stock-transactions', [ApiStockController::class, 'create']);
    Route::get('/stock-transactions', [ApiStockController::class, 'getMyTransactions']);

    // Targets
    Route::get('/targets/current', [ApiTargetController::class, 'getCurrentMonthTarget']);
});