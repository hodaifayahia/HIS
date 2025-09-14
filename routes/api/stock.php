<?php

use App\Http\Controllers\Stock\ServiceProductSettingController;
use Illuminate\Support\Facades\Route;

// Test route to verify file is loaded
Route::get('/test-stock-routes', function () {
    return response()->json(['message' => 'Stock routes are loaded']);
});

// Product Settings Routes
Route::prefix('services/{serviceId}/products/{productId}/settings')->group(function () {
    Route::get('/', [ServiceProductSettingController::class, 'show']);
    Route::post('/', [ServiceProductSettingController::class, 'store']);
    Route::delete('/', [ServiceProductSettingController::class, 'destroy']);
});

// Bulk operations
Route::prefix('services/{serviceId}/product-settings')->group(function () {
    Route::get('/', [ServiceProductSettingController::class, 'index']);
    Route::post('/bulk', [ServiceProductSettingController::class, 'bulkUpdate']);
});
