<?php

use App\Http\Controllers\Admission\AdmissionController;
use App\Http\Controllers\Admission\AdmissionProcedureController;
use App\Http\Controllers\Admission\AdmissionTreatmentController;
use Illuminate\Support\Facades\Route;

// Admission endpoints
Route::get('/admissions', [AdmissionController::class, 'index']);
Route::post('/admissions', [AdmissionController::class, 'store']);
Route::get('/admissions/statistics', [AdmissionController::class, 'statistics']);
Route::get('/admissions/active', [AdmissionController::class, 'active']);
Route::get('/admissions/{admission}', [AdmissionController::class, 'show']);
Route::patch('/admissions/{admission}', [AdmissionController::class, 'update']);
Route::delete('/admissions/{admission}', [AdmissionController::class, 'destroy']);
Route::post('/admissions/{admission}/discharge', [AdmissionController::class, 'discharge']);

// Admission Procedure endpoints
Route::get('/admissions/{admission}/procedures', [AdmissionProcedureController::class, 'index']);
Route::post('/admissions/{admission}/procedures', [AdmissionProcedureController::class, 'store']);
Route::get('/admissions/{admission}/procedures/{procedure}', [AdmissionProcedureController::class, 'show']);
Route::patch('/admissions/{admission}/procedures/{procedure}', [AdmissionProcedureController::class, 'update']);
Route::post('/admissions/{admission}/procedures/{procedure}/complete', [AdmissionProcedureController::class, 'complete']);
Route::post('/admissions/{admission}/procedures/{procedure}/cancel', [AdmissionProcedureController::class, 'cancel']);

// Admission Treatment endpoints (patient movements/entries)
Route::get('/admissions/{admission}/treatments', [AdmissionTreatmentController::class, 'index']);
Route::post('/admissions/{admission}/treatments', [AdmissionTreatmentController::class, 'store']);
Route::get('/admissions/{admission}/treatments/{treatment}', [AdmissionTreatmentController::class, 'show']);
Route::patch('/admissions/{admission}/treatments/{treatment}', [AdmissionTreatmentController::class, 'update']);
Route::delete('/admissions/{admission}/treatments/{treatment}', [AdmissionTreatmentController::class, 'destroy']);

// Verify file number endpoint
Route::post('/admissions/{admission}/verify-file-number', [AdmissionController::class, 'verifyFileNumber']);
