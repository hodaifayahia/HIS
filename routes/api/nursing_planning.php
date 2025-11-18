<?php

use App\Http\Controllers\Nursing\NursingEmergencyPlanningController;
use Illuminate\Support\Facades\Route;

Route::prefix('nursing-emergency-planning')->middleware('api')->group(function () {
    Route::get('/', [NursingEmergencyPlanningController::class, 'index']);
    Route::post('/', [NursingEmergencyPlanningController::class, 'store']);
    Route::get('/{planning}', [NursingEmergencyPlanningController::class, 'show']);
    Route::put('/{planning}', [NursingEmergencyPlanningController::class, 'update']);
    Route::delete('/{planning}', [NursingEmergencyPlanningController::class, 'destroy']);

    Route::get('/data/nurses', [NursingEmergencyPlanningController::class, 'getNurses']);
    Route::get('/data/services', [NursingEmergencyPlanningController::class, 'getServices']);

    Route::get('/overview/monthly', [NursingEmergencyPlanningController::class, 'getMonthlyOverview']);
    Route::post('/check-conflicts', [NursingEmergencyPlanningController::class, 'checkConflicts']);
    Route::get('/next-available-time', [NursingEmergencyPlanningController::class, 'getNextAvailableTime']);
    Route::get('/day-overview', [NursingEmergencyPlanningController::class, 'getDayOverview']);
    Route::post('/copy-planning', [NursingEmergencyPlanningController::class, 'copyMonthPlanning']);
    Route::get('/print-report', [NursingEmergencyPlanningController::class, 'generatePrintReport']);
});
