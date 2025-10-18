<?php

use App\Http\Controllers\ConsulationController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

// Consultations
Route::post('/consulations', [ConsulationController::class, 'store']);
Route::get('/consultations/{consultationId}', [ConsulationController::class, 'show']);
Route::get('/consultations/by-appointment/{appointmentid}', [ConsulationController::class, 'getConsultationByAppointmentId']);
Route::get('/consulations/{patientid}', [ConsulationController::class, 'index']);
Route::put('/consultations/{id}', [ConsulationController::class, 'update']);
Route::delete('/consulations/{id}', [ConsulationController::class, 'destroy']);
Route::get('/consulations/search', [ConsulationController::class, 'search']);
Route::get('/templates/content', [ConsulationController::class, 'getTemplateContent']);
Route::post('/consultation/convert-to-pdf', [ConsulationController::class, 'convertToPdf']);
Route::apiResource('/templates', TemplateController::class);
Route::get('/templates/search', [TemplateController::class, 'search']);

// Consultation specific routes (nested under /consultation)
Route::prefix('/consultation')->group(function () {
    Route::get('/patients/{patientid}', [ConsulationController::class, 'GetPatientConsulationIndex']);
    Route::post('/generate-word', [ConsulationController::class, 'generateWord']);
    Route::get('/{consultationId}', [ConsulationController::class, 'show']);
    Route::post('/generate-pdf', [ConsulationController::class, 'generatePdf']);
    Route::post('/convert-to-pdf', [ConsulationController::class, 'convertToPdf']);
    Route::post('/{patientId}/save-pdf', [ConsulationController::class, 'savePdf']);
    Route::get('/{patientId}/documents', [ConsulationController::class, 'GetPatientConsultaionDoc']);
});