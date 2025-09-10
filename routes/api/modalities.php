<?php

use App\Http\Controllers\CONFIGURATION\ModalityController;
use Illuminate\Support\Facades\Route;

// Modalities specific routes
Route::prefix('modalities')->group(function () {
    Route::get('/', [ModalityController::class, 'index']);
    Route::get('/{id}', [ModalityController::class, 'show']); // show
    Route::get('filter-options', [ModalityController::class, 'getFilterOptions']);
    Route::post('advanced-search', [ModalityController::class, 'advancedSearch']);
    Route::get('export', [ModalityController::class, 'export']);
    Route::get('dropdown/modality-types', [ModalityController::class, 'getModalityTypesForDropdown']);
    Route::get('dropdown/physical-locations', [ModalityController::class, 'getPhysicalLocationsForDropdown']);
    Route::get('dropdown/services', [ModalityController::class, 'getServicesForDropdown']);
});

Route::apiResource('/modalities', ModalityController::class); // Keep this if you need default resource routes outside the prefix group