<?php

use App\Http\Controllers\DoctorEmergencyPlanningController;
use Illuminate\Support\Facades\Route;

Route::prefix('doctor-emergency-planning')->middleware('api')->group(function () {
    // Main CRUD operations
    Route::get('/', [DoctorEmergencyPlanningController::class, 'index']);
    Route::post('/', [DoctorEmergencyPlanningController::class, 'store']);
    Route::get('/{planning}', [DoctorEmergencyPlanningController::class, 'show']);
    Route::put('/{planning}', [DoctorEmergencyPlanningController::class, 'update']);
    Route::delete('/{planning}', [DoctorEmergencyPlanningController::class, 'destroy']);

    // Helper endpoints
    Route::get('/data/doctors', [DoctorEmergencyPlanningController::class, 'getDoctors']);
    Route::get('/data/services', [DoctorEmergencyPlanningController::class, 'getServices']);

    // Planning management
    Route::get('/overview/monthly', [DoctorEmergencyPlanningController::class, 'getMonthlyOverview']);
    Route::post('/check-conflicts', [DoctorEmergencyPlanningController::class, 'checkConflicts']);
});
