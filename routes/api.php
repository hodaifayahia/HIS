<?php

use Illuminate\Support\Facades\Route;

// Include your API route files
require __DIR__.'/api/users.php';
require __DIR__.'/api/doctors.php';
require __DIR__.'/api/specializations.php';
require __DIR__.'/api/appointments.php';
require __DIR__.'/api/schedules.php';
require __DIR__.'/api/patients.php';
require __DIR__.'/api/settings.php';
require __DIR__.'/api/waitlists.php';
require __DIR__.'/api/importance_enum.php';
require __DIR__.'/api/excluded_dates.php';
require __DIR__.'/api/placeholders_attributes.php';
require __DIR__.'/api/medications.php';
require __DIR__.'/api/opinion_requests.php';
require __DIR__.'/api/consultations.php';
require __DIR__.'/api/import_export.php';
require __DIR__.'/api/medical_dashboard.php';
require __DIR__.'/api/folders.php';
require __DIR__.'/api/consultation_workspaces.php';
require __DIR__.'/api/prescriptions.php';
require __DIR__.'/api/configuration.php';
require __DIR__.'/api/modality_appointments.php';
require __DIR__.'/api/annexes.php';
require __DIR__.'/api/patient_consultation_history.php';
require __DIR__.'/api/modalities.php';
require __DIR__.'/api/infrastructure.php';
require __DIR__.'/api/crm.php';
require __DIR__.'/api/b2b.php';
require __DIR__.'/api/service_demands.php';
require __DIR__.'/api/purchasing.php';
require __DIR__.'/api/emergency_planning.php';

// Direct emergency planning routes for testing
use App\Http\Controllers\DoctorEmergencyPlanningController;

Route::prefix('doctor-emergency-planning')->group(function () {
    Route::get('/', [DoctorEmergencyPlanningController::class, 'index']);
    Route::post('/', [DoctorEmergencyPlanningController::class, 'store']);
    Route::get('/data/doctors', [DoctorEmergencyPlanningController::class, 'getDoctors']);
    Route::get('/data/services', [DoctorEmergencyPlanningController::class, 'getServices']);
    Route::get('/overview/monthly', [DoctorEmergencyPlanningController::class, 'getMonthlyOverview']);
    Route::get('/next-available-time', [DoctorEmergencyPlanningController::class, 'getNextAvailableTime']);
    Route::get('/day-overview', [DoctorEmergencyPlanningController::class, 'getDayOverview']);
    Route::post('/check-conflicts', [DoctorEmergencyPlanningController::class, 'checkConflicts']);
    Route::get('/{planning}', [DoctorEmergencyPlanningController::class, 'show']);
    Route::put('/{planning}', [DoctorEmergencyPlanningController::class, 'update']);
    Route::delete('/{planning}', [DoctorEmergencyPlanningController::class, 'destroy']);
});

require __DIR__.'/api/general_api.php'; // For any leftovers
