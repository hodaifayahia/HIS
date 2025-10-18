<?php

use App\Http\Controllers\AppointmentAvailableMonthController;
use App\Http\Controllers\ExcludedDates;
use Illuminate\Support\Facades\Route;

// Excluded Dates
Route::get('/excluded-dates/{doctorId}', [ExcludedDates::class, 'index']);
Route::post('/excluded-dates', [ExcludedDates::class, 'store']);
Route::put('/excluded-dates/{id}', [ExcludedDates::class, 'update']);
Route::delete('/excluded-dates/delete-by-date', [ExcludedDates::class, 'destroyByDate']);
Route::get('/monthwork/{doctorid}', [AppointmentAvailableMonthController::class, 'index']);