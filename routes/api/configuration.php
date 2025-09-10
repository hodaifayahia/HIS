<?php

use App\Http\Controllers\CONFIGURATION\ModalityTypeController;
use App\Http\Controllers\CONFIGURATION\PrestationController;
use App\Http\Controllers\CONFIGURATION\RemiseController;
use App\Http\Controllers\CONFIGURATION\ServiceController;
use App\Http\Controllers\CONFIGURATION\UserPaymentMethodController;
use Illuminate\Support\Facades\Route;

// Configuration Services
Route::apiResource('/services', ServiceController::class);
Route::apiResource('/modality-types', ModalityTypeController::class);
Route::apiResource('/prestation', PrestationController::class);

Route::apiResource('/user-payment-methods', UserPaymentMethodController::class);
Route::apiResource('/remise', RemiseController::class);
Route::get('/payment-methods', [UserPaymentMethodController::class, 'getPaymentMethods']);

// Prestation specific routes
Route::prefix('/prestation')->group(function () {
    Route::post('/import', [PrestationController::class, 'import']);
    Route::get('/filter-options', [PrestationController::class, 'getFilterOptions']);
    Route::get('/statistics', [PrestationController::class, 'getStatistics']);
    Route::patch('/{id}/toggle-status', [PrestationController::class, 'toggleStatus']);
});