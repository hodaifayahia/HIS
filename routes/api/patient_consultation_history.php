<?php

use App\Http\Controllers\AllergyController;
use App\Http\Controllers\ChronicDiseaseController;
use App\Http\Controllers\FamilyHistoryController;
use App\Http\Controllers\SurgicalController;
use Illuminate\Support\Facades\Route;

// Patient Consultation History (Nested Resources)
Route::prefix('consultation/patients/{patient}')->group(function () {
    Route::apiResource('allergies', AllergyController::class);
    Route::apiResource('chronic-diseases', ChronicDiseaseController::class);
    Route::apiResource('family-history', FamilyHistoryController::class);
    Route::apiResource('surgical-history', SurgicalController::class);
});