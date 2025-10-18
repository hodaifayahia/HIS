<?php

use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

// Schedule Routes
Route::get('/schedules/{doctorid}', [ScheduleController::class, 'index']);
Route::put('/schedules/{doctorid}', [ScheduleController::class, 'updateSchedule']);
Route::delete('/schedules/{doctorid}', [ScheduleController::class, 'destroy']);