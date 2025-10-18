<?php

use App\Http\Controllers\DoctorController;
use Illuminate\Support\Facades\Route;

// Doctor Routes
Route::get('/doctors', [DoctorController::class, 'index']);
Route::post('/doctors', [DoctorController::class, 'store']);
Route::put('/doctors/{doctorid}', [DoctorController::class, 'update']);
Route::delete('/doctors/{doctorid}', [DoctorController::class, 'destroy']);
Route::delete('/doctors', [DoctorController::class, 'bulkDelete']);
Route::get('/doctors/search', [DoctorController::class, 'search']);
Route::get('/doctors/WorkingDates', [DoctorController::class, 'WorkingDates']);
Route::get('/doctors/{doctorId}', [DoctorController::class, 'getDoctor']);
Route::get('/doctors/handel/specific', [DoctorController::class, 'getspecific']);
Route::get('/doctors/specializations/{specializationId}', [DoctorController::class, 'GetDoctorsBySpecilaztionsthisisfortest']);