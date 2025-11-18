<?php

use App\Http\Controllers\Pharmacy\ExternalPrescriptionController;
use App\Http\Controllers\Pharmacy\PharmacyProductController;
use Illuminate\Support\Facades\Route;

// External Prescriptions API Routes
Route::prefix('external-prescriptions')->group(function () {
    Route::get('/', [ExternalPrescriptionController::class, 'index']);
    Route::post('/', [ExternalPrescriptionController::class, 'store']);
    Route::get('/{id}', [ExternalPrescriptionController::class, 'show']);
    Route::delete('/{id}', [ExternalPrescriptionController::class, 'destroy']);
    Route::post('/{id}/items', [ExternalPrescriptionController::class, 'addItems']);
    Route::patch('/{id}/items/{itemId}', [ExternalPrescriptionController::class, 'updateItem']);
    Route::post('/{id}/items/{itemId}/dispense', [ExternalPrescriptionController::class, 'dispenseItem']);
    Route::post('/{id}/items/{itemId}/cancel', [ExternalPrescriptionController::class, 'cancelItem']);
    Route::delete('/{id}/items/{itemId}', [ExternalPrescriptionController::class, 'deleteItem']);
    Route::get('/{id}/pdf', [ExternalPrescriptionController::class, 'generatePDF']);
});

// Pharmacy Products API Routes
Route::prefix('pharmacy/products')->group(function () {
    Route::get('/', [PharmacyProductController::class, 'index']);
    Route::post('/', [PharmacyProductController::class, 'store']);
    Route::get('/{id}', [PharmacyProductController::class, 'show']);
    Route::put('/{id}', [PharmacyProductController::class, 'update']);
    Route::delete('/{id}', [PharmacyProductController::class, 'destroy']);
    Route::get('/search/{query}', [PharmacyProductController::class, 'search']);
});
