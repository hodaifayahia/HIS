<?php

use Illuminate\Support\Facades\Route;

// Include your API route files
require __DIR__ . '/api/users.php';
require __DIR__ . '/api/doctors.php';
require __DIR__ . '/api/specializations.php';
require __DIR__ . '/api/appointments.php';
require __DIR__ . '/api/schedules.php';
require __DIR__ . '/api/patients.php';
require __DIR__ . '/api/settings.php';
require __DIR__ . '/api/waitlists.php';
require __DIR__ . '/api/importance_enum.php';
require __DIR__ . '/api/appointment_forcer.php';
require __DIR__ . '/api/excluded_dates.php';
require __DIR__ . '/api/placeholders_attributes.php';
require __DIR__ . '/api/medications.php';
require __DIR__ . '/api/opinion_requests.php';
require __DIR__ . '/api/consultations.php';
require __DIR__ . '/api/import_export.php';
require __DIR__ . '/api/medical_dashboard.php';
require __DIR__ . '/api/folders.php';
require __DIR__ . '/api/consultation_workspaces.php';
require __DIR__ . '/api/prescriptions.php';
require __DIR__ . '/api/configuration.php';
require __DIR__ . '/api/modality_appointments.php';
require __DIR__ . '/api/annexes.php';
require __DIR__ . '/api/patient_consultation_history.php';
require __DIR__ . '/api/modalities.php';
require __DIR__ . '/api/infrastructure.php';
require __DIR__ . '/api/crm.php';
require __DIR__ . '/api/b2b.php';
require __DIR__ . '/api/general_api.php'; // For any leftovers