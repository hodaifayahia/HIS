<?php

use App\Http\Controllers\MedicationController;
use App\Http\Controllers\MedicationDoctorFavoratController;
use Illuminate\Support\Facades\Route;

// Medications
Route::apiResource('/medications', MedicationController::class);
Route::post('/medications/toggle-favorite', [MedicationDoctorFavoratController::class, 'toggleFavorite']);
Route::apiResource('/favorate', MedicationDoctorFavoratController::class);