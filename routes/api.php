<?php

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiStoreController;
use App\Http\Controllers\Api\ApiVisitController;
use App\Http\Controllers\Api\ApiSurveyController;
use App\Http\Controllers\Api\ApiStockController;
use App\Http\Controllers\Api\ApiTargetController;
use App\Http\Controllers\Api\ApiOfferController;
use App\Http\Controllers\Api\ApiOrderController;
use App\Http\Controllers\Api\ApiStoreMasterController;

// Authentication
Route::post('/login', [ApiAuthController::class, 'login']);
Route::post('/logout', [ApiAuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // Employee Profile
    Route::get('/profile', [ApiAuthController::class, 'profile']);

    // Store creation from app
    Route::post('/stores/create', [ApiStoreMasterController::class, 'createStore']);
    Route::get('/stores/categories', [ApiStoreMasterController::class, 'getCategories']);

    //resolve location
    Route::get('/resolve-location', function (\Illuminate\Http\Request $request) {
        $stateName = $request->get('state_name');
        $cityName = $request->get('city_name');
        $areaName = $request->get('area_name', '');

        if (!$stateName || !$cityName) {
            return response()->json([
                'success' => false,
                'error' => 'State and city names are required',
            ], 422);
        }

        $zoneId = null;
        $employee = $request->user()->employee;
        if ($employee) {
            $zoneId = $employee->zone_id;
        }

        $result = \App\Helpers\LocationResolverHelper::resolveLocation(
            $stateName,
            $cityName,
            $areaName,
            $zoneId
        );

        return response()->json($result);
    });

    // Assigned Stores
    Route::get('/stores', [ApiStoreController::class, 'getAssignedStores']);
    Route::get('/stores/{id}', [ApiStoreController::class, 'getStoreDetails']);
    Route::get('/stores/{id}/products', [ApiStoreController::class, 'getStoreProducts']);
    Route::get('/stores/{id}/order-history', [ApiOrderController::class, 'getStoreOrderHistory']);
    Route::get('/stores/{id}/survey-history', [ApiSurveyController::class, 'getStoreSurveyHistory']);
    Route::get('/stores/{id}/stock-history', [ApiStockController::class, 'getStoreStockHistory']);

    // Store Visit
    Route::post('/visits/check-in', [ApiVisitController::class, 'checkIn']);
    Route::post('/visits/{id}/check-out', [ApiVisitController::class, 'checkOut']);
    Route::get('/visits/today', [ApiVisitController::class, 'getTodayVisit']);

    // Survey Questions
    Route::get('/questions', [ApiSurveyController::class, 'getActiveQuestions']);
    Route::post('/visits/{visitId}/answers', [ApiSurveyController::class, 'submitAnswers']);
    Route::get('/surveys/my-history', [ApiSurveyController::class, 'getMySurveyHistory']);

    // Stock Transactions
    Route::post('/stock-transactions', [ApiStockController::class, 'create']);
    Route::get('/stock-transactions', [ApiStockController::class, 'getMyTransactions']);

    // Offers
    Route::get('/offers', [ApiOfferController::class, 'getActiveOffers']);
    Route::post('/offers/validate-promocode', [ApiOfferController::class, 'validatePromocode']);

    // Orders
    Route::post('/orders', [ApiOrderController::class, 'create']);
    Route::get('/orders', [ApiOrderController::class, 'getMyOrders']);
    Route::get('/orders/{id}', [ApiOrderController::class, 'getOrderDetails']);

    // Targets
    Route::get('/targets/current', [ApiTargetController::class, 'getCurrentMonthTarget']);

});