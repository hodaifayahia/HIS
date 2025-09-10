<?php

use App\Http\Controllers\AppointmentForcerController;
use Illuminate\Support\Facades\Route;

// Appointment Forcer Permissions
Route::get('/doctor-user-permissions', [AppointmentForcerController::class, 'getPermissions']);
Route::get('/doctor-user-permissions/ability', [AppointmentForcerController::class, 'IsAbleToForce']);
Route::post('/doctor-user-permissions', [AppointmentForcerController::class, 'updateOrCreatePermission']);