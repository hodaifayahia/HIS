<?php

use App\Http\Controllers\PrescriptionController;
use Illuminate\Support\Facades\Route;

// Prescription Routes
Route::prefix('/prescription')->group(function () {
    Route::resource('/', PrescriptionController::class); // Note: This resource applies to '/prescription'
    Route::get('/{id}/download-pdf', [PrescriptionController::class, 'downloadPrescriptionPdf']);
    Route::get('/prescription-templates', [PrescriptionController::class, 'getPrescriptionTemplates']);
    Route::post('/prescription-templates', [PrescriptionController::class, 'prescriptiontemplates']);
    Route::get('/download', [PrescriptionController::class, 'downloadPdf']);
    Route::get('/view/{appointment_id}', [PrescriptionController::class, 'viewPdf']);
    Route::get('/print', [PrescriptionController::class, 'printPrescription']);
    Route::get('prescription-templates/{templateId}/medications', [PrescriptionController::class, 'getTemplateMedications']);
});