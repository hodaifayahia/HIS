<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentStatus;
use Illuminate\Support\Facades\Route;

Route::get('/appointments/search', [AppointmentController::class, 'search']);
Route::get('/appointments/checkAvailability', [AppointmentController::class, 'checkAvailability']);
Route::get('/appointments/canceledappointments', [AppointmentController::class, 'getAllCanceledAppointments']);
Route::get('/appointments/available', [AppointmentController::class, 'AvailableAppointments']);
Route::get('/appointmentStatus/{doctorid}', [AppointmentStatus::class, 'appointmentStatus']);
Route::get('/appointment-statuses', [AppointmentStatus::class, 'allAppointmentStatuses']);
Route::get('/appointmentStatus/patient/{patientid}', [AppointmentStatus::class, 'appointmentStatusPatient']);
Route::get('/todaysAppointments/{doctorid}', [AppointmentStatus::class, 'todaysAppointments']);
Route::get('/appointments/ForceSlots', [AppointmentController::class, 'ForceAppointment']);
Route::get('/appointments/consulationappointment/{doctorid}', [AppointmentController::class, 'consulationappointment']);
Route::get('/appointments/patient/{Patientid}', [AppointmentController::class, 'ForPatient']);
Route::get('/appointments/{doctorId}/filter-by-date', [AppointmentController::class, 'filterByDate']);
Route::patch('/appointment/{appointmentId}/status', [AppointmentController::class, 'changeAppointmentStatus']);
Route::post('/appointments', [AppointmentController::class, 'store']);
Route::get('/appointments', [AppointmentController::class, 'GetAllAppointments']);
Route::put('/appointments/{appointmentid}', [AppointmentController::class, 'update']);
Route::get('/appointments/{doctorId}/{appointmentId}', [AppointmentController::class, 'getAppointment']);
Route::delete('/appointments/{appointmentid}', [AppointmentController::class, 'destroy']);
Route::post('/appointment/nextappointment/{appointmentid}', [AppointmentController::class, 'nextAppointment']);
Route::get('/appointment/pending', [AppointmentController::class, 'getPendingAppointment']);
Route::post('/appointments/Confirmation/print-confirmation-ticket', [AppointmentController::class, 'printConfirmationTicket']);
Route::post('/generate-appointments-pdf', [AppointmentController::class, 'generateAppointmentsPdf']);
Route::post('/appointments/print-ticket', [AppointmentController::class, 'printTicket']);