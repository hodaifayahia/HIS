<?php

use App\Http\Controllers\CONFIGURATION\ModalityAppointmentController;
use Illuminate\Support\Facades\Route;

// Modality Appointment Routes - Ordered to prevent conflicts
// Static routes first (most specific)
Route::get('/modality-appointments/checkModalityAvailability', [ModalityAppointmentController::class, 'checkModalityAvailability']);
Route::get('/modality-appointments/search', [ModalityAppointmentController::class, 'search']);
Route::get('/modality-appointments/import-template', [ModalityAppointmentController::class, 'downloadImportTemplate']);
Route::post('/modality-appointments/import', [ModalityAppointmentController::class, 'importAppointments']);
Route::get('/modality-appointments/modalities', [ModalityAppointmentController::class, 'getAllModalities']);

Route::get('/modality-user-permissions/ability', [ModalityAppointmentController::class, 'getModalityUserPermissions']);
Route::post('/modality-user-permissions', [ModalityAppointmentController::class, 'updateModalityUserPermission']);
Route::get('/modality-appointments/force-ability', [ModalityAppointmentController::class, 'getModalityUserForceAbility']);

// POST routes (actions)
Route::post('/modality-appointments', [ModalityAppointmentController::class, 'store']);
Route::post('/modality-appointments/print-ticket', [ModalityAppointmentController::class, 'printModalityTicket']);
Route::post('/modality-appointments/print-confirmation-ticket', [ModalityAppointmentController::class, 'printModalityConfirmationTicket']);

// PUT/PATCH routes (updates)
Route::put('/modality-appointments/{id}', [ModalityAppointmentController::class, 'update']);
Route::patch('/modality-appointments/{id}/status', [ModalityAppointmentController::class, 'changeModalityAppointmentStatus']);

// DELETE routes
Route::delete('/modality-appointments/{id}', [ModalityAppointmentController::class, 'destroy']);

// GET routes with parameters - specific patterns first
Route::get('/modality-appointments/{modalityId}/available', [ModalityAppointmentController::class, 'availableModalityAppointments']);
Route::get('/modality-appointments/{modalityId}/pending', [ModalityAppointmentController::class, 'getPendingModalityAppointment']);
Route::get('/modality-appointments/{modalityId}/canceled', [ModalityAppointmentController::class, 'getAllCanceledModalityAppointments']);

// Most general GET routes last
Route::get('/modality-appointments/{modalityId}', [ModalityAppointmentController::class, 'index']);
Route::get('/modality-appointments/{modalityId}/{appointmentId}', [ModalityAppointmentController::class, 'getModalityAppointment']);