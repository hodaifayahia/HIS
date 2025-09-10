<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\AllergyController;
use App\Http\Controllers\ApplicationController; // This serves your main SPA layout
use App\Http\Controllers\AppointmentAvailableMonthController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentForcerController;
use App\Http\Controllers\AppointmentStatus;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ChronicDiseaseController;
use App\Http\Controllers\ConsulationController;
use App\Http\Controllers\ConsultationworkspacesController;
use App\Http\Controllers\DashboradController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\ExcludedDates;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FamilyHistoryController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\ImportanceEnumController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\MedicationDoctorFavoratController;
use App\Http\Controllers\OpinionRequestController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PlaceholderController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\specializationsController;
use App\Http\Controllers\SurgicalController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\WaitListController;
use App\Http\Controllers\CONFIGURATION\ServiceController;
use App\Http\Controllers\CONFIGURATION\ModalityTypeController;
use App\Http\Controllers\CONFIGURATION\ModalityController;
use App\Http\Controllers\CONFIGURATION\ModalityAppointmentController;
use App\Http\Controllers\CONFIGURATION\PrestationController;
use App\Http\Controllers\CONFIGURATION\UserPaymentMethodController;
use App\Http\Controllers\CONFIGURATION\RemiseController;
use App\Http\Controllers\CONFIGURATION\PrestationPackageController;
use App\Http\Controllers\CRM\OrganismeController;
use App\Http\Controllers\INFRASTRUCTURE\PavilionController;
use App\Http\Controllers\INFRASTRUCTURE\RoomTypeController;
use App\Http\Controllers\INFRASTRUCTURE\RoomController;
use App\Http\Controllers\INFRASTRUCTURE\BedController;
use App\Http\Controllers\INFRASTRUCTURE\InfrastructureDashboardController;
use App\Http\Controllers\B2B\ConventionController;
use App\Http\Controllers\B2B\AgreementsController;
use App\Http\Controllers\B2B\ConventionDetailController;
use App\Http\Controllers\B2B\AnnexController;
use App\Http\Controllers\B2B\PrestationPricingController;
use App\Http\Controllers\B2B\AvenantController;
use App\Http\Controllers\CRM\OrganismeContactController;
use App\Http\Controllers\Auth\LoginController; // Assuming you have a LoginController to handle the actual login process
use App\Http\Controllers\B2B\ConvenctionDashborad; // Import the controller
//ficheNavetteController
use App\Http\Controllers\Reception\ficheNavetteController;

use App\Http\Controllers\Reception\UserRemiseNotificationController;
use App\Http\Controllers\Reception\RemiseRequestNotificationController;
use App\Http\Controllers\Reception\ficheNavetteItemController;
use App\Http\Controllers\Reception\FicheNavetteCustomPackageController;
use App\Http\Controllers\Reception\RemiseApproverController;
use Illuminate\Support\Facades\Route;


// --- Unauthenticated Routes ---
// Redirect root URL to the login page if the user is not authenticated
Route::get('/', function () {
    return redirect('/login');
})->middleware('guest');

// Serve the login page with your Vue Login component
Route::get('/login', function () {
    return view('auth.login'); // This will load resources/views/login.blade.php
})->name('login')->middleware('guest');

// Handle the POST request for login (this is what your Vue component hits)
// Route::post('/login', [LoginController::class, 'login']);

// --- Authenticated Routes ---
Route::middleware(['auth'])->group(function () {

    // Redirect authenticated users from the root to their dashboard (or /home)
    Route::get('/', function () {
        return redirect('/dashboard'); // Or '/home' if that's your main SPA starting point
    });

    // Logout Route
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

    // --- API Routes (Prefix all with '/api') ---
    Route::prefix('/api')->group(function () {
        // User Routes
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/receptionist', [UserController::class, 'GetReceptionists']);
        Route::post('/users', [UserController::class, 'store']);
        Route::delete('/users', [UserController::class, 'bulkDelete'])->name('users.bulkDelete');
        Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
        Route::put('/users/{userid}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/users/{userid}/change-role', [UserController::class, 'ChangeRole'])->name('users.ChangeRole');
        Route::get('/loginuser', [UserController::class, 'getCurrentUser']);
        Route::get('/role', [UserController::class, 'role']);

        Route::apiResource('/roles', RoleController::class);

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

        // Specializations Routes
        Route::get('/specializations', [specializationsController::class, 'index']);
        Route::post('/specializations', [specializationsController::class, 'store']);
        Route::put('/specializations/{id}', [specializationsController::class, 'update']);
        Route::delete('/specializations/{id}', [specializationsController::class, 'destroy']);

        Route::post('/appointments/check-same-day-availability', [AppointmentController::class, 'checkSameDayAvailability']);
        Route::get('/appointments/search', [AppointmentController::class, 'search']);
        Route::get('/appointments/checkAvailability', [AppointmentController::class, 'checkAvailability']);
        Route::get('/appointments/canceledappointments', [AppointmentController::class, 'getAllCanceledAppointments']);
        Route::get('/appointments/available', [AppointmentController::class, 'AvailableAppointments']);
        Route::get('/appointmentStatus/{doctorid}', [AppointmentStatus::class, 'appointmentStatus']);
        Route::get('/appointment-statuses', [AppointmentStatus::class, 'allAppointmentStatuses']);
        Route::get('/appointmentStatus/patient/{patientid}', [AppointmentStatus::class, 'appointmentStatusPatient']);
        Route::get('/todaysAppointments/{doctorid}', [AppointmentStatus::class, 'todaysAppointments']);
        Route::get('/appointments/ForceSlots', [AppointmentController::class, 'ForceAppointment']);
        Route::get('/appointments/{doctorid}', [AppointmentController::class, 'index']);
        Route::get('/appointments/consulationappointment/{doctorid}', [AppointmentController::class, 'consulationappointment']);
        Route::get('/appointments/patient/{Patientid}', [AppointmentController::class, 'ForPatient']);
        Route::get('/appointments/{doctorId}/filter-by-date', [AppointmentController::class, 'filterByDate']);
        Route::patch('/appointment/{appointmentId}/status', [AppointmentController::class, 'changeAppointmentStatus']);
        Route::post('/appointments', [AppointmentController::class, 'store']);
        Route::post('/appointments/book-same-day', [AppointmentController::class, 'bookSameDayAppointment']);
        Route::post('/appointments/add-to-waiting-list', [AppointmentController::class, 'addToWaitingList']);
        Route::get('/appointments', [AppointmentController::class, 'GetAllAppointments']);
        Route::put('/appointments/{appointmentid}', [AppointmentController::class, 'update']);
        Route::get('/appointments/{doctorId}/{appointmentId}', [AppointmentController::class, 'getAppointment']);
        Route::delete('/appointments/{appointmentid}', [AppointmentController::class, 'destroy']);
        Route::post('/appointment/nextappointment/{appointmentid}', [AppointmentController::class, 'nextAppointment']);
        Route::get('/appointment/pending', [AppointmentController::class, 'getPendingAppointment']);
        Route::post('/appointments/Confirmation/print-confirmation-ticket', [AppointmentController::class, 'printConfirmationTicket']);
        Route::post('/generate-appointments-pdf', [AppointmentController::class, 'generateAppointmentsPdf']);
        Route::post('/appointments/print-ticket', [AppointmentController::class, 'printTicket']);

        // Schedule Routes
        Route::get('/schedules/{doctorid}', [ScheduleController::class, 'index']);
        Route::put('/schedules/{doctorid}', [ScheduleController::class, 'updateSchedule']);
        Route::delete('/schedules/{doctorid}', [ScheduleController::class, 'destroy']);

        // Patient Routes
        Route::get('/patients', [PatientController::class, 'index']);
        Route::post('/patients', [PatientController::class, 'store']);
        Route::put('/patients/{patientid}', [PatientController::class, 'update']);
        Route::get('/patient/{PatientId}', [PatientController::class, 'PatientAppointments']);
        Route::get('/patients/search', [PatientController::class, 'search']);
        Route::get('/patients/{parentid}', [PatientController::class, 'SpecificPatient']);
        Route::delete('/patients/{patientid}', [PatientController::class, 'destroy']);

        // Setting Routes
        // This is the problematic route
        Route::get('/setting/user', [SettingController::class, 'index']);
        Route::put('/setting/user', [SettingController::class, 'update']);
        Route::put('/setting/password', [SettingController::class, 'updatePassword']);

        // Waitlist Routes
        Route::post('/waitlists', [WaitListController::class, 'store']);
        Route::post('/waitlists/count/{doctorid}', [WaitListController::class, 'countForYouAndNotForYou']);
        Route::put('/waitlists/{waitlist}', [WaitListController::class, 'update']);
        Route::get('/waitlists', [WaitListController::class, 'index']);
        Route::get('/waitlists/ForDoctor', [WaitListController::class, 'GetForDoctor']);
        Route::get('/waitlists/search', [WaitListController::class, 'search']);
        Route::get('/waitlists/filter', [WaitlistController::class, 'filter']);
        Route::post('/waitlists/{waitlist}/add-to-appointments', [WaitListController::class, 'AddPaitentToAppointments']);
        Route::post('/waitlists/{waitlistid}/move-to-end', [WaitListController::class, 'moveToend']);
        Route::delete('/waitlists/{waitlist}', [WaitListController::class, 'destroy']);
        Route::patch('/waitlists/{waitlist}/importance', [WaitlistController::class, 'updateImportance']);
        Route::get('/waitlist/empty', [WaitListController::class, 'isempty']);

        // Importance Enum
        Route::get('/importance-enum', [ImportanceEnumController::class, 'index']);

        // Appointment Forcer Permissions
        Route::get('/doctor-user-permissions', [AppointmentForcerController::class, 'getPermissions']);
        Route::get('/doctor-user-permissions/ability', [AppointmentForcerController::class, 'IsAbleToForce']);
        Route::post('/doctor-user-permissions', [AppointmentForcerController::class, 'updateOrCreatePermission']);

        // Excluded Dates
        Route::get('/excluded-dates/{doctorId}', [ExcludedDates::class, 'index']);
        Route::post('/excluded-dates', [ExcludedDates::class, 'store']);
        Route::put('/excluded-dates/{id}', [ExcludedDates::class, 'update']);
        Route::delete('/excluded-dates/delete-by-date', [ExcludedDates::class, 'destroyByDate']);
        Route::get('/monthwork/{doctorid}', [AppointmentAvailableMonthController::class, 'index']);

        // Placeholders and Attributes
        Route::resource('placeholders', PlaceholderController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('placeholders/consultation-attributes/save', [PlaceholderController::class, 'saveConsultationAttributes']);
        Route::get('placeholders/consultation-attributes/search-values', [AttributeController::class, 'searchAttributeValues']);
        Route::get('/placeholders/consultation/{appointmentid}/attributes', [PlaceholderController::class, 'getConsultationPlaceholderAttributes']);

        // Medications
        Route::apiResource('/medications', MedicationController::class);
        Route::post('/medications/toggle-favorite', [MedicationDoctorFavoratController::class, 'toggleFavorite']);
        Route::apiResource('/favorate', MedicationDoctorFavoratController::class);

        // Opinion Requests
        Route::apiResource('/opinion-requests', OpinionRequestController::class);
        Route::post('/opinion-requests/{id}/reply', [OpinionRequestController::class, 'reply']);

        // Attributes
        Route::post('/attributes', [AttributeController::class, 'store']);
        Route::get('/attributes/{id}', [AttributeController::class, 'index']);
        Route::put('/attributes/{id}', [AttributeController::class, 'update']);
        Route::delete('/attributes/{id}', [AttributeController::class, 'destroy']);
        Route::get('/attributes/search', [AttributeController::class, 'search']);
        Route::get('/attributes/metadata', [AttributeController::class, 'getMetadata']);

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

        // Import/Export
        Route::post('/import/users', [ImportController::class, 'ImportUsers']);
        Route::get('/export/users', [ExportController::class, 'ExportUsers']);
        Route::post('/import/Patients', [ImportController::class, 'ImportPatients']);
        Route::get('/export/Patients', [ExportController::class, 'ExportPatients']);
        Route::post('/import/placeholders', [ImportController::class, 'Importplaceholders']);
        Route::get('/export/placeholders', [ExportController::class, 'Exportplaceholders']);
        Route::post('/import/attributes', [ImportController::class, 'ImportAttributes']);
        Route::get('/export/attributes', [ExportController::class, 'ExportAttributes']);
        Route::post('/import/appointment/{doctorid}', [ImportController::class, 'ImportAppointment']);
        Route::get('/export/appointment', [ExportController::class, 'ExportAppointment']);

        // Medical Dashboard
        Route::get('/medical-dashboard', [DashboradController::class, 'index']);

        // Folders
        Route::apiResource('/folders', FolderController::class);
        Route::get('folders/{folder}/templates', [FolderController::class, 'getTemplates']);
        Route::get('folders/search', [FolderController::class, 'search']);

        // Consultation specific routes (nested under /api/consultation)
        Route::prefix('/consultation')->group(function () {
            Route::get('/patients/{patientid}', [ConsulationController::class, 'GetPatientConsulationIndex']);
            Route::post('/generate-word', [ConsulationController::class, 'generateWord']);
            Route::get('/{consultationId}', [ConsulationController::class, 'show']);
            Route::post('/generate-pdf', [ConsulationController::class, 'generatePdf']);
            Route::post('/convert-to-pdf', [ConsulationController::class, 'convertToPdf']);
            Route::post('/{patientId}/save-pdf', [ConsulationController::class, 'savePdf']);
            Route::get('/{patientId}/documents', [ConsulationController::class, 'GetPatientConsultaionDoc']);
        });

        // Consultation Workspaces
        Route::apiResource('/consultationworkspaces', ConsultationworkspacesController::class);
        Route::get('/consultationworkspaces/search', [ConsultationworkspacesController::class, 'search']);
        Route::get('/details/consultationworkspaces', [ConsultationworkspacesController::class, 'getworkspaceDetails']);
        Route::post('/details/consultationworkspaces', [ConsultationworkspacesController::class, 'storeworkDetails']);
        Route::delete('/details/consultationworkspaces', [ConsultationworkspacesController::class, 'DeleteworkspaceDetails']);

        // Prescription Routes
        Route::prefix('/prescription')->group(function () {
            Route::resource('/', PrescriptionController::class);
            Route::get('/{id}/download-pdf', [PrescriptionController::class, 'downloadPrescriptionPdf']);
            Route::get('/prescription-templates', [PrescriptionController::class, 'getPrescriptionTemplates']);
            Route::post('/prescription-templates', [PrescriptionController::class, 'prescriptiontemplates']);
            Route::get('/download', [PrescriptionController::class, 'downloadPdf']);
            Route::get('/view/{appointment_id}', [PrescriptionController::class, 'viewPdf']);
            Route::get('/print', [PrescriptionController::class, 'printPrescription']);
            Route::get('prescription-templates/{templateId}/medications', [PrescriptionController::class, 'getTemplateMedications']);
        });

        // Configuration Services
        Route::apiResource('/services', ServiceController::class);
        Route::apiResource('/modality-types', ModalityTypeController::class);
        Route::apiResource('/prestation', PrestationController::class);
        Route::apiResource('/prestation-packages', PrestationPackageController::class)->except(['create', 'edit']);
        Route::post('/prestation-packages/{prestationPackage}/clone', [PrestationPackageController::class, 'clone']);

        // Route::apiResource('/modality-appointments', ModalityAppointmentController::class);

      // Modality Appointment Routes - Ordered to prevent conflicts

// Static routes first (most specific)

        // Appointment Routes
        Route::get('/modality-appointments/checkModalityAvailability', [ModalityAppointmentController::class, 'checkModalityAvailability']);
        Route::get('/modality-appointments/search', [ModalityAppointmentController::class, 'search']);
        Route::get('/modality-appointments/import-template', [ModalityAppointmentController::class, 'downloadImportTemplate']);
        Route::post('/modality-appointments/import', [ModalityAppointmentController::class, 'importAppointments']);
        Route::get('/modality-appointments/modalities', [ModalityAppointmentController::class, 'getAllModalities']);

       Route::get('/modality-user-permissions/ability', [ModalityAppointmentController::class, 'getModalityUserPermissions']);
        
       Route::post('/modality-user-permissions', [ModalityAppointmentController::class, 'updateModalityUserPermission']);
        Route::get('/modality-appointments/search', [ModalityAppointmentController::class, 'search']);
           Route::get('/modality-appointments/force-ability', [ModalityAppointmentController::class, 'getModalityUserForceAbility']);
            Route::get('/modality-appointments/modalities', [ModalityAppointmentController::class, 'getAllModalities']);
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

        // Annexes
        Route::get('/annex/contract/{contractId}', [AnnexController::class, 'getByContract']);
        Route::apiResource('annex', AnnexController::class);
        // This route seems like a typo: /api/annex/12/check-{itemToDelete}/check-relations
        // Consider if you meant /api/annex/{id}/check-relations as defined below.
        Route::get('/api/annex/12/check-{itemToDelete}/check-relations', [AnnexController::class, 'checkRelations']); // VERIFY THIS ROUTE
        Route::post('/annex/{contractId}', [AnnexController::class, 'storeWithContract']);
        Route::get('/annex/{id}/check-relations', [AnnexController::class, 'checkRelations']);

        // Prestation specific routes
        Route::prefix('/prestation')->group(function () {
            Route::post('/import', [PrestationController::class, 'import']);
            Route::get('/filter-options', [PrestationController::class, 'getFilterOptions']);
            Route::get('/statistics', [PrestationController::class, 'getStatistics']);
            Route::patch('/{id}/toggle-status', [PrestationController::class, 'toggleStatus']);
        });

        // Patient Consultation History (Nested Resources)
        Route::prefix('consultation/patients/{patient}')->group(function () {
            Route::apiResource('allergies', AllergyController::class);
            Route::apiResource('chronic-diseases', ChronicDiseaseController::class);
            Route::apiResource('family-history', FamilyHistoryController::class);
            Route::apiResource('surgical-history', SurgicalController::class);
        });

        // Modalities specific routes

        Route::prefix('modalities')->group(function () {
            Route::get('/', [ModalityController::class, 'index']);
            //show 

            Route::get('/{id}', [ModalityController::class, 'show']);

            Route::get('filter-options', [ModalityController::class, 'getFilterOptions']);
            Route::post('advanced-search', [ModalityController::class, 'advancedSearch']);
            Route::get('export', [ModalityController::class, 'export']);
            Route::get('dropdown/modality-types', [ModalityController::class, 'getModalityTypesForDropdown']);
            Route::get('dropdown/physical-locations', [ModalityController::class, 'getPhysicalLocationsForDropdown']);
            Route::get('dropdown/services', [ModalityController::class, 'getServicesForDropdown']);
        });



        // Infrastructure Dashboard
        Route::get('/dashboard/infrastructure/stats', [InfrastructureDashboardController::class, 'stats']);
        Route::get('/infrastructure/recent-activity', [InfrastructureDashboardController::class, 'recentActivity']);

        // Organism
        Route::get('/organismes/settings', [OrganismeController::class, 'OrganismesSettings']);
        Route::apiResource('/organismes', OrganismeController::class);
        Route::apiResource('/organisme-contacts', OrganismeContactController::class);
        Route::apiResource('/modalities', ModalityController::class);

        // Pavilions, Conventions, Rooms, Beds
        Route::get('/convention/dashboard', [ConvenctionDashborad::class, 'getDashboardData']);

        Route::get('/conventions/family-authorization', [ConventionController::class, 'getFamilyAuthorization']);        Route::apiResource('/pavilions', PavilionController::class);
        Route::apiResource('/conventions', ConventionController::class);
        Route::patch('/conventions/{conventionId}/activate', [ConventionController::class, 'activate']);
        Route::patch('/conventions/{conventionId}/expire', [ConventionController::class, 'expire']);
        Route::get('/prestation/annex/price', [PrescriptionController::class, 'getAnnexPrestation']);
        // Verify this route:
        Route::post('/organismes/settings', [ConventionController::class, 'activateConvenation']);
        Route::apiResource('/agreements', AgreementsController::class);
        Route::apiResource('/rooms', RoomController::class); // This replaces duplicate entries for 'rooms'
        
 // User Payment Method routes
 Route::get('/remise/user', [RemiseController::class, 'userRemise']);

    Route::get('user-payment-methods', [\App\Http\Controllers\CONFIGURATION\UserPaymentMethodController::class, 'index']);
    Route::post('user-payment-methods', [\App\Http\Controllers\CONFIGURATION\UserPaymentMethodController::class, 'store']);
    Route::get('user-payment-methods/{user}', [\App\Http\Controllers\CONFIGURATION\UserPaymentMethodController::class, 'show']);
    Route::put('user-payment-methods/{user}', [\App\Http\Controllers\CONFIGURATION\UserPaymentMethodController::class, 'update']);
    Route::delete('user-payment-methods/{user}', [\App\Http\Controllers\CONFIGURATION\UserPaymentMethodController::class, 'destroy']);
    
    // Payment methods enum
    Route::get('payment-methods', [\App\Http\Controllers\CONFIGURATION\UserPaymentMethodController::class, 'getPaymentMethods']);
    Route::apiResource('/remise', RemiseController::class); // This replaces duplicate entries for 'userPaymentAccess'
    Route::prefix('/remise')->group(function () {
        Route::get('/user', [RemiseController::class, 'userRemise']);
        Route::post('/apply', [RemiseController::class, 'applyRemise']);
        Route::get('/patient', [RemiseController::class, 'patientShare']);

    });
   Route::prefix('/reception/remise-requests')->group(function () {
    Route::post('/', [RemiseRequestNotificationController::class, 'createRequest']);
    Route::get('/notifications', [RemiseRequestNotificationController::class, 'getNotifications']);
    Route::get('/pending', [RemiseRequestNotificationController::class, 'getPendingRequests']);
    Route::get('/history', [RemiseRequestNotificationController::class, 'getRequestHistory']);
    Route::patch('/{id}/approve', [RemiseRequestNotificationController::class, 'approve']);
    Route::patch('/{id}/reject', [RemiseRequestNotificationController::class, 'reject']);
    Route::patch('/notifications/mark-read', [RemiseRequestNotificationController::class, 'markAsRead']);
    
    Route::prefix('{remise_request}')->group(function () {
        Route::patch('/approve', [RemiseRequestNotificationController::class, 'approve']);
        Route::patch('/reject', [RemiseRequestNotificationController::class, 'reject']);
        Route::patch('/apply-salary', [RemiseRequestNotificationController::class, 'applyToSalary']);
    });
});

        // Route::get('/payment-methods', [UserPaymentMethodController::class, 'getPaymentMethods']);

        // ConventionDetailController
        Route::prefix('/convention/agreementdetails')->group(function () {
            Route::get('{conventionId}', [ConventionDetailController::class, 'getDetailsByConvention']);
            Route::put('{conventionId}/{detailId}', [ConventionDetailController::class, 'update']);
            Route::get('avenant/{avenantId}', [ConventionDetailController::class, 'getDetailsByAvenant']);
            Route::put('avenant/{avenantId}/{detailId}', [ConventionDetailController::class, 'update']);
        });

        Route::get('/pavilions/{pavilionId}/services', [PavilionController::class, 'PavilionServices']);
        Route::apiResource('/room-types', RoomTypeController::class);
        Route::apiResource('/beds', BedController::class);
        Route::get('/beds/availablerooms', [BedController::class, 'getAvailableRooms']);

        // Prestation Pricing
        Route::prefix('/prestation-pricings')->group(function () {
            Route::get('/', [PrestationPricingController::class, 'index']);
            Route::post('/', [PrestationPricingController::class, 'store']);
            Route::put('/{id}', [PrestationPricingController::class, 'update']);
            Route::patch('/{id}', [PrestationPricingController::class, 'update']);
            Route::delete('/{id}', [PrestationPricingController::class, 'destroy']);
            Route::post('/bulk-delete', [PrestationPricingController::class, 'bulkDelete']);
            Route::get('/convention/{conventionId}', [PrestationPricingController::class, 'getPrestationsByConvention']);

            Route::get('/avenant/{avenantId}', [PrestationPricingController::class, 'getPrestationsByAvenantId']);
        });
        Route::get('prestations/available-for-avenant/{avenantId}', [PrestationPricingController::class, 'getAvailablePrestations']);
        Route::get('/prestations/available-for-service-avenant/{serviceId}/{avenantId}', [PrestationPricingController::class, 'getAvailablePrestationsForServiceAndAvenant']);
        Route::get('/prestations/available-for-service-annex/{serviceId}/{annexId}', [PrestationPricingController::class, 'getAvailablePrestationsForServiceAndAnnex']);
        Route::get('/prestations/allavailable-for-service-annex/{serviceId}/{annexId}', [PrestationPricingController::class, 'getallAvailablePrestationsForServiceAndAnnex']);

        // Avenants
        Route::prefix('/avenants')->group(function () {
            Route::post('/convention/{conventionId}/duplicate', [AvenantController::class, 'createAvenantAndDuplicatePrestations']);
            Route::patch('/{avenantId}/activate', [AvenantController::class, 'activateAvenant']);
            Route::get('/{avenantId}', [AvenantController::class, 'getAvenantById']);
            Route::get('/convention/{conventionId}/pending', [AvenantController::class, 'checkPendingAvenantByConventionId']);
            Route::get('/convention/{conventionId}', [AvenantController::class, 'getAvenantsByConventionId']);
        });


        // Reception APIs - MOVE THIS INSIDE THE /api PREFIX GROUP
        Route::apiResource('/fiche-navette-custom-packages', FicheNavetteCustomPackageController::class);

        Route::prefix('/reception')->group(function () {
            Route::post('/fiche-navette/{ficheNavetteId}/items', [ficheNavetteItemController::class, 'store']);
            Route::get('/fiche-navette/{ficheNavetteId}/items', [ficheNavetteItemController::class, 'index']);
            Route::put('/fiche-navette/{ficheNavetteId}/items/{itemId}', [ficheNavetteItemController::class, 'update']);
            Route::delete('/fiche-navette/{ficheNavetteId}/items/{itemId}', [ficheNavetteItemController::class, 'destroy']);
            Route::get('/prestations/all', [ficheNavetteController::class, 'getAllPrestations']);

            Route::apiResource('/fiche-navette', ficheNavetteController::class);
                    
            // Add these new routes for convention pricing
            Route::get('/prestations/with-convention-pricing', [ficheNavetteItemController::class, 'getPrestationsWithConventionPricing']);
            Route::get('/prestations/by-convention/{conventionId}', [ficheNavetteItemController::class, 'getPrestationsByConvention']);

            Route::patch('/fiche-navette/{ficheNavette}/status', [ficheNavetteController::class, 'changeStatus']);
            Route::post('/fiche-navette/{ficheNavette}/prestations', [ficheNavetteController::class, 'addPrestation']);
            Route::put('/fiche-navette/{ficheNavette}/prestations/{item}', [ficheNavetteController::class, 'updatePrestation']);
            Route::delete('/fiche-navette/{ficheNavette}/prestations/{item}', [ficheNavetteItemController::class, 'removePrestation']);
            Route::get('/fiche-navette/prestations/packages/{packageId}', [ficheNavetteItemController::class, 'getPrestationsByPackage']);
            // Services and doctors
            Route::get('/prestations/by-service/{serviceId}', [ficheNavetteController::class, 'getPrestationsByService']);
            Route::get('/packages/by-service/{serviceId}', [ficheNavetteController::class, 'getPackagesByService']);
                // Specialization-based routes

                    Route::delete('/dependencies/{dependencyId}', [ficheNavetteItemController::class, 'removeDependency']);

            Route::get('/prestations/by-specialization/{specializationId}', [ficheNavetteController::class, 'getPrestationsBySpecialization']);
            Route::get('/packages/by-specialization/{specializationId}', [ficheNavetteController::class, 'getPackagesBySpecialization']);
            Route::post('/prestations/dependencies', [ficheNavetteController::class, 'getPrestationsDependencies']);
            Route::get('/prestations/dependencies', [ficheNavetteController::class, 'getPrestationsDependencies']);
            // Dependencies and search
            Route::get('/patients/{patientId}/conventions', [ficheNavetteItemController::class, 'getPatientConventions']);
            Route::delete('/dependencies/{dependencyId}', [ficheNavetteItemController::class, 'removeDependency']);
            Route::get('/prestations/with-packages', [ficheNavetteController::class, 'getAllPrestationsWithPackages']);
            Route::get('/prestations/dependencies', [ficheNavetteController::class, 'getPrestationsDependencies']);
            Route::get('/prestations/search', [ficheNavetteController::class, 'searchPrestations']);
            Route::get('/specializations/all', [ficheNavetteController::class, 'getAllSpecializations']);
            Route::get('/fiche-navette/{ficheNavetteId}/grouped-items', [ficheNavetteItemController::class, 'getGroupedByInsured']);
        });


        // portal apiresource
Route::prefix('portal')->group(function () {
    Route::apiResource('remise-approvers', RemiseApproverController::class);
    Route::post('remise-approvers/{remiseApprover}/toggle', [RemiseApproverController::class, 'toggleApproval']);
    Route::post('remise-approvers/bulk-update', [RemiseApproverController::class, 'bulkUpdate']);
});
        // Convention prescription routes
        Route::post('/fiche-navette/{ficheNavetteId}/convention-prescription', [ficheNavetteItemController::class, 'storeConventionPrescription']);
    }); // End of /api group

    // The main application entry point for authenticated users
    Route::get('/{view}', [ApplicationController::class, '__invoke'])->where('view', '.*')->name('dashboard');
}); // End of authenticated middleware group