<?php

use App\Http\Controllers\PatientController;
use Illuminate\Support\Facades\Route;

// Patient Routes
Route::get('/patients', [PatientController::class, 'index']);
Route::post('/patients', [PatientController::class, 'store']);
Route::put('/patients/{patientid}', [PatientController::class, 'update']);
Route::get('/patient/{PatientId}', [PatientController::class, 'PatientAppointments']);
Route::get('/patients/search', [PatientController::class, 'search']);
Route::get('/patients/{parentid}', [PatientController::class, 'SpecificPatient']);
Route::delete('/patients/{patientid}', [PatientController::class, 'destroy']);