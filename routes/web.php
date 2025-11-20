

<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admission\AdmissionController;
use App\Http\Controllers\Admission\AdmissionDocumentController; // This serves your main SPA layout
use App\Http\Controllers\Admission\AdmissionProcedureController;
use App\Http\Controllers\Api\ApprovalPersonController;
use App\Http\Controllers\Api\BonCommendApprovalController;
use App\Http\Controllers\Appointment\AppointmentAvailableMonthController;
use App\Http\Controllers\Appointment\AppointmentController;
use App\Http\Controllers\Appointment\AppointmentForcerController;
use App\Http\Controllers\Appointment\AppointmentStatus;
use App\Http\Controllers\Attribute\AttributeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordValidationController;
use App\Http\Controllers\B2B\AgreementsController;
use App\Http\Controllers\B2B\AnnexController;
use App\Http\Controllers\B2B\AvenantController;
use App\Http\Controllers\B2B\ConvenctionDashborad;
use App\Http\Controllers\B2B\ConventionController;
use App\Http\Controllers\B2B\ConventionDetailController;
use App\Http\Controllers\B2B\PrestationPricingController;
use App\Http\Controllers\Bank\BankAccountController;
use App\Http\Controllers\Bank\BankAccountTransactionController;
use App\Http\Controllers\Bank\BankAccountTransactionPackController;
use App\Http\Controllers\Bank\BankController;
use App\Http\Controllers\Bank\DashboardController;
use App\Http\Controllers\Caisse\CaisseTransferController;
use App\Http\Controllers\Caisse\FinancialTransactionController;
use App\Http\Controllers\Coffre\CaisseController;
use App\Http\Controllers\Coffre\CaisseSessionController;
use App\Http\Controllers\Coffre\CoffreController;
use App\Http\Controllers\Coffre\CoffreTransactionController;
use App\Http\Controllers\CONFIGURATION\ModalityAppointmentController;
use App\Http\Controllers\CONFIGURATION\ModalityController;
use App\Http\Controllers\CONFIGURATION\ModalityTypeController;
use App\Http\Controllers\CONFIGURATION\PrestationController;
use App\Http\Controllers\CONFIGURATION\PrestationPackageController;
use App\Http\Controllers\CONFIGURATION\RemiseController;
use App\Http\Controllers\CONFIGURATION\ServiceController;
use App\Http\Controllers\CONFIGURATION\TransferApprovalController;
use App\Http\Controllers\CONFIGURATION\UserCaisseApprovalController;
use App\Http\Controllers\CONFIGURATION\UserPaymentMethodController;
use App\Http\Controllers\CONFIGURATION\UserRefundPermissionController;
use App\Http\Controllers\Consultation\ConsulationController;
use App\Http\Controllers\Consultation\ConsultationworkspacesController;
use App\Http\Controllers\Consultation\OpinionRequestController;
use App\Http\Controllers\Consultation\PrescriptionController;
use App\Http\Controllers\Core\ApplicationController;
use App\Http\Controllers\CRM\OrganismeContactController;
use App\Http\Controllers\CRM\OrganismeController;
use App\Http\Controllers\Dashboard\DashboradController;
use App\Http\Controllers\Doctor\DoctorController;
use App\Http\Controllers\Doctor\DoctorEmergencyPlanningController;
use App\Http\Controllers\Doctor\ExcludedDates;
use App\Http\Controllers\Doctor\specializationsController;
use App\Http\Controllers\FactureProforma\FactureProformaController;
use App\Http\Controllers\FileManagement\FolderController;
use App\Http\Controllers\ImportExport\ExportController;
use App\Http\Controllers\ImportExport\ImportController;
use App\Http\Controllers\INFRASTRUCTURE\BedController;
use App\Http\Controllers\INFRASTRUCTURE\InfrastructureDashboardController;
use App\Http\Controllers\INFRASTRUCTURE\PavilionController;
use App\Http\Controllers\INFRASTRUCTURE\RoomController; // Assuming you have a LoginController to handle the actual login process
use App\Http\Controllers\INFRASTRUCTURE\RoomTypeController; // Import the controller
use App\Http\Controllers\Inventory\InventoryAuditProductController; // Password validation controller
// ficheNavetteController
use App\Http\Controllers\Inventory\ServiceGroupProductPricingController;
use App\Http\Controllers\manager\RefundAuthorizationController;
use App\Http\Controllers\manager\TransactionBankRequestController;
use App\Http\Controllers\Nursing\NursingEmergencyPlanningController;
use App\Http\Controllers\Nursing\PatientConsumptionController;
use App\Http\Controllers\Patient\AllergyController;
use App\Http\Controllers\Patient\ChronicDiseaseController;
use App\Http\Controllers\Patient\FamilyHistoryController;
use App\Http\Controllers\Patient\MedicationController;
use App\Http\Controllers\Patient\MedicationDoctorFavoratController;
use App\Http\Controllers\Patient\PatientController;
use App\Http\Controllers\Patient\SurgicalController;
use App\Http\Controllers\Pharmacy\ExternalPrescriptionController;
use App\Http\Controllers\Pharmacy\PharmacyInventoryController;
use App\Http\Controllers\Pharmacy\PharmacyProductController;
use App\Http\Controllers\Pharmacy\PharmacyStockageController;
use App\Http\Controllers\Pharmacy\PharmacyStockMovementController;
use App\Http\Controllers\Product\ProductHistoryController;
use App\Http\Controllers\Purchasing\BonCommendController;
use App\Http\Controllers\Purchasing\BonEntreeController;
use App\Http\Controllers\Purchasing\BonRetourController;
use App\Http\Controllers\Purchasing\ConsignmentReceptionController;
use App\Http\Controllers\Purchasing\PurchasingProductController;
use App\Http\Controllers\Reception\ficheNavetteController;
use App\Http\Controllers\Reception\FicheNavetteCustomPackageController;
use App\Http\Controllers\Reception\ficheNavetteItemController;
use App\Http\Controllers\Reception\PrestationPackageDoctorAssignmentController;
use App\Http\Controllers\Reception\RemiseApproverController;
use App\Http\Controllers\Reception\RemiseRequestNotificationController;
use App\Http\Controllers\Schedule\ScheduleController;
use App\Http\Controllers\Settings\ImportanceEnumController;
use App\Http\Controllers\Settings\PlaceholderController;
use App\Http\Controllers\Settings\SellingSettingsController;
use App\Http\Controllers\Settings\SettingController;
use App\Http\Controllers\Stock\ReserveController;
use App\Http\Controllers\Template\TemplateController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\WaitList\WaitListController;
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
Route::post('/login', [LoginController::class, 'login']);

// --- Authenticated Routes ---
Route::middleware(['auth'])->group(function () {

    // Load the main SPA for authenticated users
    Route::get('/', function () {
        return view('admin.layout.app');
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
        Route::get('/user-info', [UserController::class, 'getUserInfo']); // Get user info with all assigned services
        Route::get('/role', [UserController::class, 'role']);

        // Password Validation Routes
        Route::post('/auth/validate-password', [PasswordValidationController::class, 'validateCurrentUserPassword']);
        Route::post('/auth/validate-user-password', [PasswordValidationController::class, 'validateUserPassword']);

        // Convention Routes - Must be before any resource routes that might conflict
        Route::get('/conventions/contract-percentages', [ConventionController::class, 'getContractPercentages']);

        Route::apiResource('/roles', RoleController::class);

        // Doctor Routes
        Route::get('/doctors', [DoctorController::class, 'index']);
        Route::post('/doctors', [DoctorController::class, 'store']);
        Route::post('/doctors/{doctorId}/duplicate', [DoctorController::class, 'duplicate']);
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

        Route::get('/appointments/checkAvailability', [AppointmentController::class, 'checkAvailability']);
        Route::get('/appointments/search', [AppointmentController::class, 'search']);
        Route::get('/appointments/canceledappointments', [AppointmentController::class, 'getAllCanceledAppointments']);
        Route::get('/appointments/available', [AppointmentController::class, 'AvailableAppointments']);
        Route::get('/appointmentStatus/{doctorid}', [AppointmentStatus::class, 'appointmentStatus']);
        Route::get('/appointment-statuses', [AppointmentStatus::class, 'allAppointmentStatuses']);
        Route::get('/appointmentStatus/patient/{patientid}', [AppointmentStatus::class, 'appointmentStatusPatient']);
        Route::get('/todaysAppointments/{doctorid}', [AppointmentStatus::class, 'todaysAppointments']);
        Route::get('/appointments/ForceSlots', [AppointmentController::class, 'ForceAppointment']);
        Route::get('/appointments/{doctorid}', [AppointmentController::class, 'index']);
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
        Route::post('/appointments/transfer', [AppointmentController::class, 'transferAppointments']);

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

        // Patient Portal Routes
        Route::get('/patients/{patientid}/details', [PatientController::class, 'show']);
        Route::put('/patients/{patientid}/notes', [PatientController::class, 'updateNotes']);
        Route::put('/patients/{patientid}/medical-info', [PatientController::class, 'updateMedicalInfo']);
        Route::get('/appointments/patient/{patientid}', [AppointmentController::class, 'getPatientAppointments']);
        Route::get('/consulations/{patientid}/all', [ConsulationController::class, 'getPatientConsultations']);

        // Setting Routes
        // This is the problematic route
        Route::get('/setting/user', [SettingController::class, 'index']);
        Route::put('/setting/user', [SettingController::class, 'update']);
        Route::put('/setting/password', [SettingController::class, 'updatePassword']);

        // Selling Settings Routes
        Route::prefix('/selling-settings')->group(function () {
            Route::get('/', [SellingSettingsController::class, 'index']);
            Route::post('/', [SellingSettingsController::class, 'store']);
            Route::get('/services', [SellingSettingsController::class, 'getServices']);
            Route::post('/active-percentage', [SellingSettingsController::class, 'getActivePercentage']);
            Route::get('/{id}', [SellingSettingsController::class, 'show']);
            Route::put('/{id}', [SellingSettingsController::class, 'update']);
            Route::delete('/{id}', [SellingSettingsController::class, 'destroy']);
            Route::post('/{id}/toggle-active', [SellingSettingsController::class, 'toggleActive']);
        });

        // Service Groups Routes
        Route::prefix('/service-groups')->group(function () {
            Route::get('/', [\App\Http\Controllers\Settings\ServiceGroupController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Settings\ServiceGroupController::class, 'store']);
            Route::get('/available-services', [\App\Http\Controllers\Settings\ServiceGroupController::class, 'getAvailableServices']);
            Route::get('/all-services', [\App\Http\Controllers\Settings\ServiceGroupController::class, 'getAllServices']);
            Route::get('/{id}', [\App\Http\Controllers\Settings\ServiceGroupController::class, 'show']);
            Route::put('/{id}', [\App\Http\Controllers\Settings\ServiceGroupController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Settings\ServiceGroupController::class, 'destroy']);
            Route::post('/{id}/toggle-active', [\App\Http\Controllers\Settings\ServiceGroupController::class, 'toggleActive']);
            Route::post('/{id}/add-service', [\App\Http\Controllers\Settings\ServiceGroupController::class, 'addService']);
            Route::post('/{id}/remove-service', [\App\Http\Controllers\Settings\ServiceGroupController::class, 'removeService']);
            Route::post('/{id}/reorder-services', [\App\Http\Controllers\Settings\ServiceGroupController::class, 'reorderServices']);
        });

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
        Route::get('/waitlist/empty', [WaitListController::class, 'isempty']);  // Request Transaction Approvals
        Route::get('/request-transaction-approvals', [\App\Http\Controllers\Approval\RequestTransactionApprovalController::class, 'index']);
        Route::patch('/request-transaction-approvals/{requestTransactionApproval}/approve', [\App\Http\Controllers\Approval\RequestTransactionApprovalController::class, 'approve']);
        Route::patch('/request-transaction-approvals/{requestTransactionApproval}/reject', [\App\Http\Controllers\Approval\RequestTransactionApprovalController::class, 'reject']);

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
        Route::post('placeholders/{placeholderId}/duplicate', [PlaceholderController::class, 'duplicate']);
        Route::post('placeholders/consultation-attributes/save', [PlaceholderController::class, 'saveConsultationAttributes']);
        Route::get('placeholders/consultation-attributes/search-values', [AttributeController::class, 'searchAttributeValues']);
        Route::get('/placeholders/consultation/{appointmentid}/attributes', [PlaceholderController::class, 'getConsultationPlaceholderAttributes']);

        // Medications
        Route::apiResource('/medications', MedicationController::class);
        Route::post('/medications/toggle-favorite', [MedicationDoctorFavoratController::class, 'toggleFavorite']);
        Route::post('/medication-favorites/duplicate', [MedicationDoctorFavoratController::class, 'duplicateFavorites']);
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
        Route::get('/consulations/consulationappointment/{doctorid}', [ConsulationController::class, 'consulationappointment']);
        Route::put('/consultations/{id}', [ConsulationController::class, 'update']);
        Route::delete('/consulations/{id}', [ConsulationController::class, 'destroy']);
        Route::get('/consulations/search', [ConsulationController::class, 'search']);
        Route::get('/templates/content', [ConsulationController::class, 'getTemplateContent']);
        Route::post('/consultation/convert-to-pdf', [ConsulationController::class, 'convertToPdf']);
        Route::apiResource('/templates', TemplateController::class);
        Route::post('/templates/{templateId}/duplicate', [TemplateController::class, 'duplicate']);
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

        // Salles Management
        Route::apiResource('/salles', \App\Http\Controllers\CONFIGURATION\SalleController::class);
        Route::post('/salles/{salle}/assign-specializations', [\App\Http\Controllers\CONFIGURATION\SalleController::class, 'assignSpecializations']);
        Route::post('/salles/{salle}/remove-specializations', [\App\Http\Controllers\CONFIGURATION\SalleController::class, 'removeSpecializations']);
        Route::get('/salles/specializations/available', [\App\Http\Controllers\CONFIGURATION\SalleController::class, 'getAvailableSpecializations']);

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
            // show

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

        // Config Routes
        Route::get('/config/relation-types', [\App\Http\Controllers\Config\ConfigController::class, 'getRelationTypes']);

        // Organism
        Route::get('/organismes/settings', [OrganismeController::class, 'OrganismesSettings']);
        Route::apiResource('/organismes', OrganismeController::class);
        Route::apiResource('/organisme-contacts', OrganismeContactController::class);
        Route::apiResource('/modalities', ModalityController::class);

        // Pavilions, Conventions, Rooms, Beds
        Route::get('/convention/dashboard', [ConvenctionDashborad::class, 'getDashboardData']);

        Route::get('/conventions/family-authorization', [ConventionController::class, 'getFamilyAuthorization']);
        Route::apiResource('/pavilions', PavilionController::class);
        Route::apiResource('/conventions', ConventionController::class);
        Route::patch('/conventions/{conventionId}/activate', [ConventionController::class, 'activate']);
        Route::patch('/conventions/{conventionId}/expire', [ConventionController::class, 'expire']);
        Route::post('/conventions/{conventionId}/extend', [ConventionController::class, 'extend']);
        Route::get('/conventions/contract-percentages', [ConventionController::class, 'getContractPercentages']);

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
            Route::post('/fiche-prestations', [RemiseRequestNotificationController::class, 'getFichePrestations']);

            // NEW: Group contributions routes
            Route::get('/group/{groupId}/contributions', [RemiseRequestNotificationController::class, 'getGroupContributions']);
            Route::post('/drafts', [RemiseRequestNotificationController::class, 'saveContributionDrafts']);
            Route::patch('/contributions/{contributionId}/status', [RemiseRequestNotificationController::class, 'updateContributionStatus']);

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
            // Prestation Package Doctor Assignments
            Route::prefix('/packages')->group(function () {
                // Get prestations with doctors for a package
                Route::get('/{packageId}/prestations-with-doctors',
                    [PrestationPackageDoctorAssignmentController::class, 'getPrestationsWithDoctors']);

                // Store doctor assignments (bulk)
                Route::post('/{packageId}/doctor-assignments',
                    [PrestationPackageDoctorAssignmentController::class, 'storeDoctorAssignments']);

                // Update single doctor assignment
                Route::put('/{packageId}/prestations/{prestationId}/doctor',
                    [PrestationPackageDoctorAssignmentController::class, 'updateDoctorAssignment']);

                // Remove doctor assignment
                Route::delete('/{packageId}/prestations/{prestationId}/doctor',
                    [PrestationPackageDoctorAssignmentController::class, 'removeDoctorAssignment']);

                // Bulk update doctor assignments (replaces all)
                Route::patch('/{packageId}/doctor-assignments/bulk',
                    [PrestationPackageDoctorAssignmentController::class, 'bulkUpdateDoctorAssignments']);
            });

            // Fiche Navette Item Doctor Assignments for Packages
            Route::prefix('/fiche-navette-items')->group(function () {
                // Get package doctors for a fiche navette item
                Route::get('/{itemId}/package-doctors',
                    [PrestationPackageDoctorAssignmentController::class, 'getPackageItemDoctors']);
            });

            Route::post('/fiche-navette/{ficheNavetteId}/items', [ficheNavetteItemController::class, 'store']);
            Route::get('/fiche-navette/{ficheNavetteId}/items', [ficheNavetteItemController::class, 'index']);
            Route::get('/fiche-navette/{ficheNavetteId}/filtered-prestations', [ficheNavetteController::class, 'getPrestationsForFicheByAuthenticatedUser']);

            Route::put('/fiche-navette/{ficheNavetteId}/items/{itemId}', [ficheNavetteItemController::class, 'update']);
            // Update a dependency's stored default_payment_type
            // Support multiple HTTP verbs in case the server or client uses POST/PATCH instead of PUT
            Route::put('/fiche-navette/dependencies/{dependencyId}', [ficheNavetteItemController::class, 'updateDependency']);
            Route::patch('/fiche-navette/dependencies/{dependencyId}', [ficheNavetteItemController::class, 'updateDependency']);
            Route::post('/fiche-navette/dependencies/{dependencyId}', [ficheNavetteItemController::class, 'updateDependency']);
            Route::delete('/fiche-navette/{ficheNavetteId}/items/{itemId}', [ficheNavetteItemController::class, 'destroy']);
            Route::post('/fiche-navette/{ficheNavetteId}/convert-to-package', [ficheNavetteItemController::class, 'convertToPackage']);
            // NEW: Create package with strict doctor validation
            Route::post('/fiche-navette/{ficheNavetteId}/create-package', [ficheNavetteItemController::class, 'createPackageWithDoctorValidation']);
            Route::get('/fiche-navette/today-pending', [ficheNavetteController::class, 'getPrestationsForTodayAndPendingByAuthenticatedUser']);
            Route::get('/prestations/all', [ficheNavetteController::class, 'getAllPrestations']);
            Route::post('/fiche-navette/{prestationId}/update-status', [FicheNavetteController::class, 'updatePrestationStatus']);

            // Print fiche navette ticket (must be before apiResource to avoid route conflict)
            Route::post('/fiche-navette/{id}/print-ticket', [ficheNavetteController::class, 'printFicheNavetteTicket']);

            // Toggle patient faithful status
            Route::post('/fiche-navette/{ficheNavetteId}/toggle-patient-faithful', [ficheNavetteController::class, 'togglePatientFaithful']);

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

        Route::prefix('bon-retours')->group(function () {
            Route::get('/', [BonRetourController::class, 'index']);
            Route::post('/', [BonRetourController::class, 'store']);
            Route::get('/statistics', [BonRetourController::class, 'statistics']);
            Route::get('/{bonRetour}', [BonRetourController::class, 'show']);
            Route::put('/{bonRetour}', [BonRetourController::class, 'update']);
            Route::delete('/{bonRetour}', [BonRetourController::class, 'destroy']);
            // PDF routes
            Route::get('/{bonRetour}/pdf', [BonRetourController::class, 'generatePdf'])->name('bon-retours.pdf');
            Route::get('/{id}/download-pdf', [BonRetourController::class, 'downloadPdf'])->name('bon-retours.download-pdf');
            // Status actions
            Route::post('/{bonRetour}/submit', [BonRetourController::class, 'submitForApproval']);
            Route::post('/{bonRetour}/approve', [BonRetourController::class, 'approve']);
            Route::post('/{bonRetour}/complete', [BonRetourController::class, 'complete']);
            Route::post('/{bonRetour}/cancel', [BonRetourController::class, 'cancel']);

            // PDF generation
            Route::get('/{bonRetour}/pdf', [BonRetourController::class, 'generatePdf']);
        });

        // Emergency routes - same as reception but with /emergency prefix
        Route::prefix('/emergency')->group(function () {
            // apiResource for fiche-navette CRUD operations
            Route::apiResource('/fiche-navette', ficheNavetteController::class);

            Route::post('/fiche-navette/{ficheNavetteId}/items', [ficheNavetteItemController::class, 'store']);
            Route::get('/fiche-navette/{ficheNavetteId}/items', [ficheNavetteItemController::class, 'index']);
            Route::get('/fiche-navette/{ficheNavetteId}/filtered-prestations', [ficheNavetteController::class, 'getPrestationsForFicheByAuthenticatedUser']);

            Route::put('/fiche-navette/{ficheNavetteId}/items/{itemId}', [ficheNavetteItemController::class, 'update']);
            Route::delete('/fiche-navette/{ficheNavetteId}/items/{itemId}', [ficheNavetteItemController::class, 'destroy']);
            Route::get('/fiche-navette/today-pending', [ficheNavetteController::class, 'getPrestationsForTodayAndPendingByAuthenticatedUser']);
            Route::get('/prestations/all', [ficheNavetteController::class, 'getAllPrestations']);
            Route::post('/fiche-navette/{prestationId}/update-status', [FicheNavetteController::class, 'updatePrestationStatus']);

            // Print fiche navette ticket (must be before apiResource to avoid route conflict)
            Route::post('/fiche-navette/{id}/print-ticket', [ficheNavetteController::class, 'printFicheNavetteTicket']);

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
        Route::post('/fiche-navette/{ficheNavetteId}/convention-prescription', [ficheNavetteItemController::class, 'storeConventionPrescription']);
        Route::apiResource('coffres', CoffreController::class);

        Route::apiResource('coffre-transactions', CoffreTransactionController::class);
        Route::get('coffre-transactions/types', [CoffreTransactionController::class, 'transactionTypes']);
        Route::get('coffre-transactions/coffres', [CoffreTransactionController::class, 'coffres']);
        Route::get('coffre-transactions/users', [CoffreTransactionController::class, 'users']);
        Route::get('coffre-transactions/recent', [CoffreTransactionController::class, 'recent']);
        Route::get('coffre-transactions/stats', [CoffreTransactionController::class, 'stats']);
        // portal apiresource
        Route::prefix('portal')->group(function () {
            Route::apiResource('remise-approvers', RemiseApproverController::class);
            Route::post('remise-approvers/{remiseApprover}/toggle', [RemiseApproverController::class, 'toggleApproval']);
            Route::post('remise-approvers/bulk-update', [RemiseApproverController::class, 'bulkUpdate']);
        });
        // Caisses resource routes
        Route::apiResource('caisses', CaisseController::class);

        // Additional routes
        Route::patch('caisses/{caisse}/toggle-status', [CaisseController::class, 'toggleStatus']);
        Route::get('caisses-services', [CaisseController::class, 'services']);
        Route::get('caisses-stats', [CaisseController::class, 'stats']);

        // Caisse Sessions resource routes
        Route::patch('caisse-sessions/{caisse_session}/close', [CaisseSessionController::class, 'close']);
        Route::apiResource('caisse-sessions', CaisseSessionController::class);

        // Additional session management routes
        Route::patch('caisse-sessions/{caisse_session}/suspend', [CaisseSessionController::class, 'suspend']);
        Route::patch('caisse-sessions/{caisse_session}/resume', [CaisseSessionController::class, 'resume']);

        // Helper routes
        Route::get('caisse-sessions-authUser', [CaisseSessionController::class, 'getUserSessions']);
        Route::get('caisse-sessions-active', [CaisseSessionController::class, 'active']);
        Route::get('caisse-sessions-caisses', [CaisseSessionController::class, 'caisses']);
        Route::get('caisse-sessions-users', [CaisseSessionController::class, 'users']);
        Route::get('caisse-sessions-coffres', [CaisseSessionController::class, 'coffres']);
        Route::get('caisse-sessions-denominations', [CaisseSessionController::class, 'denominations']);
        Route::get('caisse-sessions-stats', [CaisseSessionController::class, 'stats']);

        // Bank resource routes
        Route::apiResource('banks', BankController::class);

        // Additional bank routes
        Route::patch('banks/{bank}/toggle-status', [BankController::class, 'toggleStatus']);
        Route::get('banks-active', [BankController::class, 'active']);
        Route::get('banks-by-currency/{currency}', [BankController::class, 'byCurrency']);
        Route::get('banks-options', [BankController::class, 'options']);
        Route::get('banks-stats', [BankController::class, 'stats']);
        Route::post('banks-reorder', [BankController::class, 'reorder']);
        Route::post('banks-seed', [BankController::class, 'seed']);

        Route::apiResource('patient-consumptions', PatientConsumptionController::class);

        // Product Reserves Routes
        Route::prefix('product-reserves')->group(function () {
            Route::get('/', [\App\Http\Controllers\Product\ProductReserveController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Product\ProductReserveController::class, 'store']);
            Route::post('/bulk-fulfill', [\App\Http\Controllers\Product\ProductReserveController::class, 'bulkFulfill']);
            Route::post('/bulk-cancel', [\App\Http\Controllers\Product\ProductReserveController::class, 'bulkCancel']);
            Route::get('/{productReserve}', [\App\Http\Controllers\Product\ProductReserveController::class, 'show']);
            Route::put('/{productReserve}', [\App\Http\Controllers\Product\ProductReserveController::class, 'update']);
            Route::delete('/{productReserve}', [\App\Http\Controllers\Product\ProductReserveController::class, 'destroy']);
            Route::post('/{productReserve}/cancel', [\App\Http\Controllers\Product\ProductReserveController::class, 'cancel']);
            Route::post('/{productReserve}/fulfill', [\App\Http\Controllers\Product\ProductReserveController::class, 'fulfill']);
        });

        // Nursing Product Routes
        Route::prefix('nursing')->group(function () {
            Route::get('products', [\App\Http\Controllers\Nursing\NursingProductController::class, 'index']);
            Route::get('products/categories', [\App\Http\Controllers\Nursing\NursingProductController::class, 'categories']);
            Route::get('user-services', [\App\Http\Controllers\Nursing\NursingProductController::class, 'userServices']);
        });

        // Bank Account resource routes (accounts)
        Route::apiResource('bank-accounts', BankAccountController::class);
        Route::patch('bank-accounts/{bankAccount}/toggle-status', [BankAccountController::class, 'toggleStatus']);
        Route::patch('bank-accounts/{bankAccount}/update-balance', [BankAccountController::class, 'updateBalance']);
        Route::get('bank-accounts-active', [BankAccountController::class, 'active']);
        Route::get('bank-accounts-by-currency/{currency}', [BankAccountController::class, 'byCurrency']);
        Route::get('bank-accounts-by-bank/{bankId}', [BankAccountController::class, 'byBank']);
        Route::get('bank-accounts-stats', [BankAccountController::class, 'stats']);
        Route::get('bank-accounts-currencies', [BankAccountController::class, 'currencies']);
        Route::get('bank-accounts-banks', [BankAccountController::class, 'banks']);
        Route::post('bank-accounts-sync-balances', [BankAccountController::class, 'syncBalances']);

        // Bank Account Transaction routes
        Route::apiResource('bank-account-transactions', BankAccountTransactionController::class);

        // Additional transaction actions
        Route::patch('bank-account-transactions/{bankAccountTransaction}/complete', [BankAccountTransactionController::class, 'complete']);
        Route::patch('bank-account-transactions/{bankAccountTransaction}/cancel', [BankAccountTransactionController::class, 'cancel']);
        Route::patch('bank-account-transactions/{bankAccountTransaction}/reconcile', [BankAccountTransactionController::class, 'reconcile']);
        Route::patch('bank-account-transactions/{bankAccountTransaction}/validate', [BankAccountTransactionController::class, 'validate']);
        Route::post('bank-account-transactions/{bankAccountTransaction}/validate', [BankAccountTransactionController::class, 'validate']); // POST route for file uploads

        // Bulk upload routes
        Route::post('bank-account-transactions/bulk-upload', [BankAccountTransactionController::class, 'bulkUpload']);
        Route::get('bank-account-transactions/download-template', [BankAccountTransactionController::class, 'downloadTemplate']);
        Route::get('bank-account-transactions-stats', [BankAccountTransactionController::class, 'stats']);

        // Validation attachment download route
        Route::get('validation-attachments/{filename}', function ($filename) {
            $path = storage_path('app/public/validation_attachments/'.$filename);
            if (! file_exists($path)) {
                abort(404);
            }

            return response()->file($path);
        })->where('filename', '.*');

        // Dashboard routes
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('dashboard/system-health', [DashboardController::class, 'systemHealth']);
        Route::post('dashboard/export', [DashboardController::class, 'exportDashboard']);
        Route::get('dashboard/balance-trends', [DashboardController::class, 'balanceTrends']);
        Route::get('dashboard/transaction-analytics', [DashboardController::class, 'transactionAnalytics']);

        // Caisse Transfer routes
        Route::apiResource('caisse-transfers', CaisseTransferController::class);

        // Additional transfer actions
        Route::patch('caisse-transfers/{caisseTransfer}/accept', [CaisseTransferController::class, 'accept']);
        Route::patch('caisse-transfers/{caisseTransfer}/reject', [CaisseTransferController::class, 'reject']);
        Route::get('caisse-transfers-by-token/{token}', [CaisseTransferController::class, 'getByToken']);
        Route::get('caisse-transfers-user', [CaisseTransferController::class, 'checkIfThereIsRequest']);
        Route::get('caisse-transfers-stats', [CaisseTransferController::class, 'stats']);
        Route::post('caisse-transfers-expire-old', [CaisseTransferController::class, 'expireOld']);
        Route::get('caisse-transfers-auth-user-session', [CaisseTransferController::class, 'caisseTransfersAuthUserSession']);

        // Financial Transaction specific routes BEFORE the resource routes
        Route::apiResource('financial-transactions', FinancialTransactionController::class);

        // Additional transaction endpoints
        Route::post('financial-transactions-update-prestation-price', [FinancialTransactionController::class, 'updatePrestationPrice']);
        Route::post('financial-transactions-bulk-payment', [FinancialTransactionController::class, 'bulkPayment']);
        Route::get('financial-transactions-stats', [FinancialTransactionController::class, 'stats']);
        Route::get('financial-transactions-prestations', [FinancialTransactionController::class, 'getPrestationsWithDependencies']);
        Route::get('financial-transactions-patient-prestations', [FinancialTransactionController::class, 'getPatientPrestations']);
        Route::get('financial-transactions-by-fiche-navette', [FinancialTransactionController::class, 'getByFicheNavette']);
        Route::get('financial-transactions-by-session', [FinancialTransactionController::class, 'getBySession']);

        Route::post('financial-transactions/{financialTransaction}/refund', [FinancialTransactionController::class, 'refund']);
        Route::get('financial-transactions-daily-summary', [FinancialTransactionController::class, 'dailySummary']);
        // Refund and overpayment routes
        Route::post('financial-transactions/process-refund', [FinancialTransactionController::class, 'processRefund']);
        Route::post('financial-transactions/handle-overpayment', [FinancialTransactionController::class, 'handleOverpayment']);
        Route::get('financial-transactions/refundable', [FinancialTransactionController::class, 'getRefundableTransactions']);
        // Refund Authorization routes
        Route::apiResource('refund-authorizations', RefundAuthorizationController::class);
        Route::post('refund-authorizations/{refundAuthorization}/approve', [RefundAuthorizationController::class, 'approve']);
        Route::post('refund-authorizations/{refundAuthorization}/reject', [RefundAuthorizationController::class, 'reject']);
        Route::get('refund-authorizations/fiche-item/{ficheNavetteItemId}', [RefundAuthorizationController::class, 'getByFicheNavetteItem']);

        // Patient Tracking routes
        Route::prefix('patient-tracking')->group(function () {
            Route::post('check-in', [\App\Http\Controllers\MANAGER\PatientTrackingController::class, 'checkIn']);
            Route::post('{trackingId}/check-out', [\App\Http\Controllers\MANAGER\PatientTrackingController::class, 'checkOut']);
            Route::get('current-positions', [\App\Http\Controllers\MANAGER\PatientTrackingController::class, 'getCurrentPositions']);
            Route::get('history', [\App\Http\Controllers\MANAGER\PatientTrackingController::class, 'getHistory']);
            Route::get('available-salles/{specializationId}', [\App\Http\Controllers\MANAGER\PatientTrackingController::class, 'getAvailableSalles']);
            Route::get('salle-occupancy', [\App\Http\Controllers\MANAGER\PatientTrackingController::class, 'getSalleOccupancy']);
            Route::get('{id}', [\App\Http\Controllers\MANAGER\PatientTrackingController::class, 'show']);
        });
        // User refund permissions
        Route::get('user-refund-permissions', [UserRefundPermissionController::class, 'index']);
        Route::post('user-refund-permissions', [UserRefundPermissionController::class, 'store']);
        Route::get('user-refund-permissions/check', [UserRefundPermissionController::class, 'checkauth']);
        Route::get('user-refund-permissions/approvers', [UserRefundPermissionController::class, 'getApprovers']);
        Route::delete('user-refund-permissions/{user}', [UserRefundPermissionController::class, 'destroy']);

        // Caisse approval permissions
        Route::get('user-caisse-approval', [UserCaisseApprovalController::class, 'index']);
        Route::post('user-caisse-approval', [UserCaisseApprovalController::class, 'store']);
        Route::get('user-caisse-approval/check', [UserCaisseApprovalController::class, 'checkAuth']);
        Route::get('user-caisse-approval/approvers', [UserCaisseApprovalController::class, 'getApprovers']);
        Route::delete('user-caisse-approval/{user}', [UserCaisseApprovalController::class, 'destroy']);

        // TransactionBankRequestController

        // Transaction bank request (payment approval) routes
        Route::get('transaction-bank-requests', [TransactionBankRequestController::class, 'index']);
        Route::post('transaction-bank-requests', [TransactionBankRequestController::class, 'store']);
        // Approver acts on a request (approve/reject)
        Route::patch('transaction-bank-requests/{transactionBankRequest}/status', [TransactionBankRequestController::class, 'updateStatus']);
        // Get pending requests assigned to current approver (standard name used by frontend)
        Route::get('transaction-bank-requests/pending-approvals', [TransactionBankRequestController::class, 'getPendingApprovals']);
        // Backwards compatibility: older frontend code may call /pending
        Route::get('transaction-bank-requests/pending', [TransactionBankRequestController::class, 'getPendingApprovals']);

        // Transfer Approval routes
        Route::apiResource('transfer-approvals', TransferApprovalController::class);

        // Additional transfer approval endpoints
        Route::post('transfer-approvals/{transferApproval}/toggle-status', [TransferApprovalController::class, 'toggleStatus']);
        Route::post('transfer-approvals/check-limit', [TransferApprovalController::class, 'checkApprovalLimit']);
        Route::get('transfer-approvals-users-without-limits', [TransferApprovalController::class, 'getUsersWithoutLimits']);
        Route::get('transfer-approvals-my-limit', [TransferApprovalController::class, 'getMyApprovalLimit']);

        Route::apiResource('request-transaction-approval', TransferApprovalController::class);
        Route::apiResource('bank-account-transactions-pack', BankAccountTransactionPackController::class);
        Route::get('bank-account-transactions/{transactionId}/pack-users', [BankAccountTransactionPackController::class, 'getPackUsers']);

        // Fournisseur routes
        Route::apiResource('fournisseurs', \App\Http\Controllers\Purchasing\FournisseurController::class);
        Route::get('fournisseurs/search', [\App\Http\Controllers\Purchasing\FournisseurController::class, 'search']);
        Route::get('fournisseurs-active', [\App\Http\Controllers\Purchasing\FournisseurController::class, 'active']);

        // Stock Products - for direct editing
        Route::apiResource('products', \App\Http\Controllers\Stock\ProductController::class);

        // Pharmacy Products - Static routes first to avoid conflicts
        Route::apiResource('pharmacy-products', PharmacyProductController::class, [
            'parameters' => ['pharmacy_product' => 'id'],
        ])->names([
            'index' => 'pharmacy.products.index',
            'create' => 'pharmacy.products.create',
            'store' => 'pharmacy.products.store',
            'show' => 'pharmacy.products.show',
            'edit' => 'pharmacy.products.edit',
            'update' => 'pharmacy.products.update',
            'destroy' => 'pharmacy.products.destroy',
        ]);
        Route::prefix('pharmacy-products')->group(function () {
            Route::get('autocomplete', [PharmacyProductController::class, 'autocomplete']); // OPTIMIZED: Lightweight endpoint
            Route::get('categories', [PharmacyProductController::class, 'getCategories']);
            Route::delete('bulk-delete', [PharmacyProductController::class, 'bulkDelete']);
            Route::post('bulk-update-approval', [PharmacyProductController::class, 'bulkUpdateApproval']);
            Route::get('low-stock', [PharmacyProductController::class, 'getLowStock']);
            Route::get('critical-stock', [PharmacyProductController::class, 'getCriticalStock']);
            Route::get('controlled-substances', [PharmacyProductController::class, 'getControlledSubstances']);
            Route::get('paginated', [PharmacyProductController::class, 'paginated']); // Infinite scroll endpoint
            Route::get('prescription-only', [PharmacyProductController::class, 'getPrescriptionOnly']);
            Route::get('{id}/details', [PharmacyProductController::class, 'getDetails']);
            Route::get('{productId}/inventory', [PharmacyProductController::class, 'getProductInventory']);
            Route::get('{productId}/settings', [PharmacyProductController::class, 'getSettings']);
            Route::post('{productId}/settings', [PharmacyProductController::class, 'saveSettings']);
            Route::get('{product}/total-stock', [PharmacyProductController::class, 'getTotalStock']);
            Route::get('{productId}/stock-details', [PharmacyProductController::class, 'getStockDetails']);
        });

        // Purchasing Products - Combined endpoint for managing both stock and pharmacy products
        Route::prefix('purchasing')->group(function () {
            Route::get('products', [PurchasingProductController::class, 'index']);
            Route::post('products', [PurchasingProductController::class, 'store']);
            Route::get('products/{id}', [PurchasingProductController::class, 'show']);
            Route::put('products/{id}', [PurchasingProductController::class, 'update']);
            Route::delete('products/{id}', [PurchasingProductController::class, 'destroy']);
        });

        // Alias routes for pharmacy-products endpoint
        Route::get('pharmacy-products', [PharmacyProductController::class, 'index']);
        Route::post('pharmacy-products', [PharmacyProductController::class, 'store']);
        Route::get('pharmacy-products/{id}', [PharmacyProductController::class, 'show']);
        Route::put('pharmacy-products/{id}', [PharmacyProductController::class, 'update']);

        // External Prescriptions - Pharmacy orders created by doctors
        Route::prefix('external-prescriptions')->group(function () {
            Route::get('/', [ExternalPrescriptionController::class, 'index']);
            Route::post('/', [ExternalPrescriptionController::class, 'store']);
            Route::get('/{id}', [ExternalPrescriptionController::class, 'show']);
            Route::delete('/{id}', [ExternalPrescriptionController::class, 'destroy']);
            Route::post('/{id}/items', [ExternalPrescriptionController::class, 'addItems']);
            Route::patch('/{id}/items/{itemId}', [ExternalPrescriptionController::class, 'updateItem']);
            Route::post('/{id}/items/{itemId}/dispense', [ExternalPrescriptionController::class, 'dispenseItem']);
            Route::post('/{id}/items/{itemId}/cancel', [ExternalPrescriptionController::class, 'cancelItem']);
            Route::delete('/{id}/items/{itemId}', [ExternalPrescriptionController::class, 'deleteItem']);
            Route::get('/{id}/pdf', [ExternalPrescriptionController::class, 'generatePDF']);
        });
        Route::delete('pharmacy-products/{id}', [PharmacyProductController::class, 'destroy']);

        // Pharmacy Products alternate endpoint (with slash instead of dash)
        Route::prefix('pharmacy')->group(function () {
            Route::get('products', [PharmacyProductController::class, 'index']);
            Route::post('products', [PharmacyProductController::class, 'store']);
            Route::post('products/bulk-update-approval', [PharmacyProductController::class, 'bulkUpdateApproval']);
            Route::get('products/categories', [PharmacyProductController::class, 'getCategories']);
            Route::get('products/autocomplete', [PharmacyProductController::class, 'autocomplete']);
            Route::get('products/low-stock', [PharmacyProductController::class, 'getLowStock']);
            Route::get('products/critical-stock', [PharmacyProductController::class, 'getCriticalStock']);
            Route::get('products/controlled-substances', [PharmacyProductController::class, 'getControlledSubstances']);
            Route::get('products/paginated', [PharmacyProductController::class, 'paginated']);
            Route::get('products/prescription-only', [PharmacyProductController::class, 'getPrescriptionOnly']);
            Route::get('products/{id}/details', [PharmacyProductController::class, 'getDetails']);
            Route::get('products/{productId}/inventory', [PharmacyProductController::class, 'getProductInventory']);
            Route::get('products/{productId}/settings', [PharmacyProductController::class, 'getSettings']);
            Route::post('products/{productId}/settings', [PharmacyProductController::class, 'saveSettings']);
            Route::get('products/{product}/total-stock', [PharmacyProductController::class, 'getTotalStock']);
            Route::get('products/{productId}/stock-details', [PharmacyProductController::class, 'getStockDetails']);
            Route::get('products/{pharmacyProduct}', [PharmacyProductController::class, 'show']);
            Route::put('products/{pharmacyProduct}', [PharmacyProductController::class, 'update']);
            Route::delete('products/{pharmacyProduct}', [PharmacyProductController::class, 'destroy']);
            Route::delete('products/bulk-delete', [PharmacyProductController::class, 'bulkDelete']);
        });

        // Pharmacy Inventory - Static routes first to avoid conflicts
        Route::prefix('pharmacy/inventory')->group(function () {
            Route::get('service-stock', [PharmacyInventoryController::class, 'getServiceStock']);
            Route::get('expired', [PharmacyInventoryController::class, 'getExpired']);
            Route::get('expiring-soon', [PharmacyInventoryController::class, 'getExpiringItems']);
            Route::get('low-stock', [PharmacyInventoryController::class, 'getLowStock']);
            Route::get('critical-stock', [PharmacyInventoryController::class, 'getCriticalStock']);
            Route::get('summary', [PharmacyInventoryController::class, 'getInventorySummary']);
            Route::get('available', [PharmacyInventoryController::class, 'getAvailable']);
            Route::get('by-product/{productId}', [PharmacyInventoryController::class, 'getByProduct']);
            Route::get('by-stockage/{stockageId}', [PharmacyInventoryController::class, 'getByStockage']);
            Route::post('{inventory}/adjust', [PharmacyInventoryController::class, 'adjustStock']);
            Route::post('{inventory}/transfer', [PharmacyInventoryController::class, 'transferStock']);
            Route::post('{inventory}/generate-barcode', [PharmacyInventoryController::class, 'generateBarcode']);
        });
        Route::apiResource('pharmacy/inventory', PharmacyInventoryController::class)->names([
            'index' => 'pharmacy.inventory.index',
            'create' => 'pharmacy.inventory.create',
            'store' => 'pharmacy.inventory.store',
            'show' => 'pharmacy.inventory.show',
            'edit' => 'pharmacy.inventory.edit',
            'update' => 'pharmacy.inventory.update',
            'destroy' => 'pharmacy.inventory.destroy',
        ]);

        // Pharmacy Stockages - Static routes first to avoid conflicts
        Route::prefix('pharmacy/stockages')->group(function () {
            Route::post('bulk-status-update', [PharmacyStockageController::class, 'bulkStatusUpdate']);
            Route::get('by-service/{serviceId}', [PharmacyStockageController::class, 'getByService']);
            Route::get('temperature-controlled', [PharmacyStockageController::class, 'getTemperatureControlled']);
            Route::get('{stockage}/capacity', [PharmacyStockageController::class, 'getCapacity']);
            Route::get('{stockage}/utilization', [PharmacyStockageController::class, 'getUtilization']);
            Route::get('{stockage}/tools', [PharmacyStockageController::class, 'getTools']);
            Route::post('{stockage}/tools', [PharmacyStockageController::class, 'addTool']);
            Route::delete('{stockage}/tools/{toolId}', [PharmacyStockageController::class, 'removeTool']);
        });
        Route::apiResource('pharmacy/stockages', PharmacyStockageController::class)->names([
            'index' => 'pharmacy.stockages.index',
            'create' => 'pharmacy.stockages.create',
            'store' => 'pharmacy.stockages.store',
            'show' => 'pharmacy.stockages.show',
            'edit' => 'pharmacy.stockages.edit',
            'update' => 'pharmacy.stockages.update',
            'destroy' => 'pharmacy.stockages.destroy',
        ]);

        // Pharmacy Stock Movements - Complete routes matching stock pattern
        Route::prefix('pharmacy/stock-movements')->group(function () {
            Route::get('/', [PharmacyStockMovementController::class, 'index']);
            Route::post('/create-draft', [PharmacyStockMovementController::class, 'createDraft']);
            Route::get('/drafts', [PharmacyStockMovementController::class, 'getDrafts']);
            Route::get('/pending-approvals', [PharmacyStockMovementController::class, 'getPendingApprovals']);
            Route::get('/suggestions', [PharmacyStockMovementController::class, 'getSuggestions']);
            Route::get('/stats', [PharmacyStockMovementController::class, 'getStats']);
            Route::get('history', [PharmacyStockMovementController::class, 'getHistory']); // New route for stock movement history
            Route::get('/{movementId}', [PharmacyStockMovementController::class, 'show']);
            Route::delete('/{movementId}', [PharmacyStockMovementController::class, 'destroy']);
            Route::post('/{movementId}/send', [PharmacyStockMovementController::class, 'sendDraft']);
            Route::patch('/{movementId}/status', [PharmacyStockMovementController::class, 'updateStatus']);
            Route::get('/{movementId}/available-stock', [PharmacyStockMovementController::class, 'availableStock']);
            Route::get('/{movementId}/inventory/{productId}', [PharmacyStockMovementController::class, 'getProductInventory']);
            Route::post('/{movementId}/select-inventory', [PharmacyStockMovementController::class, 'selectInventory']);

            // Approval and rejection routes
            Route::post('/{movementId}/approve', [PharmacyStockMovementController::class, 'approveItems']);
            Route::post('/{movementId}/reject', [PharmacyStockMovementController::class, 'rejectItems']);

            // Transfer and delivery routes
            Route::post('/{movementId}/init-transfer', [PharmacyStockMovementController::class, 'initializeTransfer']);
            Route::post('/{movementId}/confirm-delivery', [PharmacyStockMovementController::class, 'confirmDelivery']);
            Route::post('/{movementId}/confirm-product', [PharmacyStockMovementController::class, 'confirmProduct']);
            Route::post('/{movementId}/finalize-confirmation', [PharmacyStockMovementController::class, 'finalizeConfirmation']);

            // Validation workflow routes
            Route::post('/{movementId}/validate-quantities', [PharmacyStockMovementController::class, 'validateQuantities']);
            Route::post('/{movementId}/process-validation', [PharmacyStockMovementController::class, 'processValidation']);

            // Item management routes
            Route::post('/{movementId}/items', [PharmacyStockMovementController::class, 'addItem']);
            Route::put('/{movementId}/items/{itemId}', [PharmacyStockMovementController::class, 'updateItem']);
            Route::delete('/{movementId}/items/{itemId}', [PharmacyStockMovementController::class, 'removeItem']);
        });
        Route::prefix('bon-commend-approvals')->group(function () {
            Route::get('/', [BonCommendApprovalController::class, 'index'])->name('api.bon-commend-approvals.index');
            Route::get('/my-pending', [BonCommendApprovalController::class, 'myPendingApprovals'])->name('api.bon-commend-approvals.my-pending');
            Route::get('/my-approvals', [BonCommendApprovalController::class, 'myApprovals'])->name('api.bon-commend-approvals.my-approvals');
            Route::get('/statistics', [BonCommendApprovalController::class, 'statistics'])->name('api.bon-commend-approvals.statistics');
            Route::get('/{approval}', [BonCommendApprovalController::class, 'show'])->name('api.bon-commend-approvals.show');
            Route::post('/{approval}/approve', [BonCommendApprovalController::class, 'approve'])->name('api.bon-commend-approvals.approve');
            Route::post('/{approval}/reject', [BonCommendApprovalController::class, 'reject'])->name('api.bon-commend-approvals.reject');
            Route::post('/{approval}/cancel', [BonCommendApprovalController::class, 'cancel'])->name('api.bon-commend-approvals.cancel');
        });

        // Pharmacy Stockage Tools (Location Management) - Matching stock pattern
        Route::prefix('pharmacy/stockages/{stockage}/tools')->group(function () {
            Route::get('/', [PharmacyStockageController::class, 'getTools']);
            Route::post('/', [PharmacyStockageController::class, 'addTool']);
            Route::get('/{toolId}', [PharmacyStockageController::class, 'showTool']);
            Route::put('/{toolId}', [PharmacyStockageController::class, 'updateTool']);
            Route::delete('/{toolId}', [PharmacyStockageController::class, 'removeTool']);
        });

        // Helper routes for pharmacy tool types and blocks
        Route::get('pharmacy/stockage-tools/types', [PharmacyStockageController::class, 'getToolTypes']);
        Route::get('pharmacy/stockage-tools/blocks', [PharmacyStockageController::class, 'getBlocks']);

        // Pharmacy Product Settings Routes (matching stock pattern)
        Route::prefix('pharmacy')->group(function () {
            Route::get('product-settings/{serviceId}/{productName}/{productForme}', [PharmacyProductController::class, 'getProductSettings']);
            Route::post('product-settings', [PharmacyProductController::class, 'storeProductSettings']);
            Route::put('product-settings/{serviceId}/{productName}/{productForme}', [PharmacyProductController::class, 'updateProductSettings']);
            Route::delete('product-settings/{serviceId}/{productName}/{productForme}', [PharmacyProductController::class, 'destroyProductSettings']);
            Route::get('product-settings/{serviceId}', [PharmacyProductController::class, 'getProductSettingsByService']);

            // Product Global Settings Routes (per product)
            Route::get('products/{product}/details', [PharmacyProductController::class, 'getDetails']);
            Route::delete('products/bulk-delete', [PharmacyProductController::class, 'bulkDelete']);
            Route::get('products/{productId}/settings', [PharmacyProductController::class, 'getGlobalSettings']);
            Route::post('products/{productId}/settings', [PharmacyProductController::class, 'storeGlobalSettings']);
            Route::get('products/{productId}/settings/{key}', [PharmacyProductController::class, 'getGlobalSetting']);
            Route::put('products/{productId}/settings/{key}', [PharmacyProductController::class, 'updateGlobalSetting']);
            Route::delete('products/{productId}/settings/{key}', [PharmacyProductController::class, 'destroyGlobalSetting']);
        });
        // Service Demand Management routes
        Route::prefix('service-demands')->group(function () {
            Route::get('/', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'store']);

            // Helper endpoints (must come BEFORE parameterized routes)
            Route::get('/meta/services', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'getServices']);
            Route::get('/meta/products', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'getProducts']);
            Route::get('/meta/fournisseurs', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'getFournisseurs']);
            Route::get('/meta/stats', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'getStats']);
            Route::get('/meta/supplier-ratings', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'getSupplierRatings']);
            Route::get('/suggestions', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'getSuggestions']);

            // Parameterized routes (must come AFTER specific named routes)
            Route::get('/{id}', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'show']);
            Route::put('/{id}', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'destroy']);

            // Status management
            Route::post('/{id}/send', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'send']);
            Route::post('/{id}/update-to-facture-proforma', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'updateToFactureProforma']);
            Route::post('/{id}/update-to-bon-commend', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'updateToBonCommend']);
            Route::post('/{id}/confirm-proforma', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'confirmProforma']);
            Route::post('/{id}/confirm-bon-commend', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'confirmBonCommend']);

            // Item management
            Route::post('/{id}/items', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'addItem']);
            Route::put('/{id}/items/{itemId}', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'updateItem']);
            Route::delete('/{id}/items/{itemId}', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'removeItem']);

            // Fournisseur assignment management
            Route::post('/{id}/items/{itemId}/assign-fournisseur', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'assignFournisseurToItem']);
            Route::post('/{id}/bulk-assign-fournisseurs', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'bulkAssignFournisseurs']);
            Route::put('/{id}/items/{itemId}/assignments/{assignmentId}', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'updateFournisseurAssignment']);
            Route::delete('/{id}/items/{itemId}/assignments/{assignmentId}', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'removeFournisseurAssignment']);

            // Facture proforma creation from assignments
            Route::post('/{id}/create-facture-proforma', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'createFactureProformaFromAssignments']);
            Route::get('/{id}/assignment-summary', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'getAssignmentSummary']);

            // Workflow tracking
            Route::post('/{id}/add-note', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'addWorkflowNote']);
        });
        Route::get('products/{productId}/history', [ProductHistoryController::class, 'getProductHistory']);

        // Inventory Audit Routes
        Route::prefix('inventory-audits')->group(function () {
            Route::get('/', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'store']);
            Route::get('/my-audits', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'myAudits']);
            Route::get('/{inventoryAudit}', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'show']);
            Route::put('/{inventoryAudit}', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'update']);
            Route::delete('/{inventoryAudit}', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'destroy']);

            // Audit actions
            Route::post('/{inventoryAudit}/start', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'start']);
            Route::post('/{inventoryAudit}/complete', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'complete']);

            // Participants
            Route::post('/{inventoryAudit}/participants', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'addParticipant']);
            Route::delete('/{inventoryAudit}/participants', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'removeParticipant']);

            // Audit items (products)
            Route::get('/{inventoryAudit}/items', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'getItems']);
            Route::post('/{inventoryAudit}/items/bulk', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'bulkUpdateItems']);
            Route::get('/{inventoryAudit}/pdf', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'generatePDF']);

            // Reconciliation
            Route::get('/{inventoryAudit}/analyze-discrepancies', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'analyzeDiscrepancies']);
            Route::post('/{inventoryAudit}/assign-recount', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'assignRecount']);
            Route::post('/{inventoryAudit}/finalize-reconciliation', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'finalizeReconciliation']);

            // Recount specific routes
            Route::post('/{inventoryAudit}/recount/assign-products', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'assignProductsForRecount']);
            Route::get('/{inventoryAudit}/recount/products/{participantId}', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'getRecountProducts']);
            Route::delete('/{inventoryAudit}/recount/participants/{participantId}', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'removeRecount']);
            Route::post('/{inventoryAudit}/recount/complete/{participantId}', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'completeRecount']);
        });

        // Legacy Inventory Audit Product Routes (keep for backward compatibility)
        Route::prefix('inventory-audit')->group(function () {
            Route::get('/products', [InventoryAuditProductController::class, 'getProductsForAudit']);
            Route::post('/save', [InventoryAuditProductController::class, 'saveAudit']);
            Route::get('/template', [InventoryAuditProductController::class, 'downloadTemplate']);
            Route::get('/export', [InventoryAuditProductController::class, 'exportToExcel']);
            Route::post('/import', [InventoryAuditProductController::class, 'importFromExcel']);
            Route::post('/report', [InventoryAuditProductController::class, 'generatePdfReport']);
            Route::get('/history', [InventoryAuditProductController::class, 'getAuditHistory']);
        });

        // Facture Proforma Management routes
        Route::prefix('facture-proformas')->group(function () {
            // Helper endpoints (must come BEFORE parameterized routes)
            Route::get('/meta/service-demands', [FactureProformaController::class, 'getServiceDemands']);
            Route::get('/meta/suppliers', [FactureProformaController::class, 'getSuppliers']);
            Route::get('/meta/products', [FactureProformaController::class, 'getProducts']);
            Route::get('/meta/stats', [FactureProformaController::class, 'getStats']);

            // Main CRUD operations
            Route::get('/', [FactureProformaController::class, 'index']);
            Route::post('/', [FactureProformaController::class, 'store']);
            Route::get('/{id}', [FactureProformaController::class, 'show']);
            Route::put('/{id}', [FactureProformaController::class, 'update']);
            Route::delete('/{id}', [FactureProformaController::class, 'destroy']);
            Route::get('/{id}/attachments/{index}/download', [FactureProformaController::class, 'downloadAttachment']);

            // Special operations
            Route::post('/create-from-demands', [FactureProformaController::class, 'createFromServiceDemands']);
            Route::post('/{id}/confirm', [FactureProformaController::class, 'confirm']);
            Route::post('/{id}/send', [FactureProformaController::class, 'send']);
            Route::get('/{id}/download', [FactureProformaController::class, 'download']);
            Route::post('/{id}/mark-as-paid', [FactureProformaController::class, 'markAsPaid']);

            // Product detail management
            Route::get('/{id}/products', [FactureProformaController::class, 'getProformaProducts']);
            Route::put('/{id}/products/{productId}', [FactureProformaController::class, 'updateProductQuantity']);
            Route::post('/{id}/cancel', [FactureProformaController::class, 'cancel']);

            // Attachment management
            Route::post('/{id}/attachments', [FactureProformaController::class, 'uploadAttachment']);
            Route::get('/{id}/attachments/{attachmentId}', [FactureProformaController::class, 'downloadAttachment']);
            Route::delete('/{id}/attachments/{attachmentId}', [FactureProformaController::class, 'deleteAttachment']);

            // Workflow confirmation
            Route::put('/{id}/confirm-workflow', [FactureProformaController::class, 'confirmWorkflow']);
        });

        Route::prefix('doctor-emergency-planning')->group(function () {
            Route::get('/', [DoctorEmergencyPlanningController::class, 'index']);
            Route::post('/', [DoctorEmergencyPlanningController::class, 'store']);
            Route::get('/data/doctors', [DoctorEmergencyPlanningController::class, 'getDoctors']);
            Route::get('/data/services', [DoctorEmergencyPlanningController::class, 'getServices']);
            Route::get('/overview/monthly', [DoctorEmergencyPlanningController::class, 'getMonthlyOverview']);
            Route::post('/check-conflicts', [DoctorEmergencyPlanningController::class, 'checkConflicts']);
            Route::get('/next-available-time', [DoctorEmergencyPlanningController::class, 'getNextAvailableTime']);
            Route::get('/day-overview', [DoctorEmergencyPlanningController::class, 'getDayOverview']);
            Route::post('/copy-planning', [DoctorEmergencyPlanningController::class, 'copyMonthPlanning']);
            Route::get('/print-report', [DoctorEmergencyPlanningController::class, 'generatePrintReport']);
            Route::get('/{planning}', [DoctorEmergencyPlanningController::class, 'show']);
            Route::put('/{planning}', [DoctorEmergencyPlanningController::class, 'update']);
            Route::delete('/{planning}', [DoctorEmergencyPlanningController::class, 'destroy']);
        });

        Route::prefix('nursing-emergency-planning')->group(function () {
            Route::get('/', [NursingEmergencyPlanningController::class, 'index']);
            Route::post('/', [NursingEmergencyPlanningController::class, 'store']);
            Route::get('/data/nurses', [NursingEmergencyPlanningController::class, 'getNurses']);
            Route::get('/data/services', [NursingEmergencyPlanningController::class, 'getServices']);
            Route::get('/overview/monthly', [NursingEmergencyPlanningController::class, 'getMonthlyOverview']);
            Route::post('/check-conflicts', [NursingEmergencyPlanningController::class, 'checkConflicts']);
            Route::get('/next-available-time', [NursingEmergencyPlanningController::class, 'getNextAvailableTime']);
            Route::get('/day-overview', [NursingEmergencyPlanningController::class, 'getDayOverview']);
            Route::post('/copy-planning', [NursingEmergencyPlanningController::class, 'copyMonthPlanning']);
            Route::get('/print-report', [NursingEmergencyPlanningController::class, 'generatePrintReport']);
            Route::get('/{planning}', [NursingEmergencyPlanningController::class, 'show']);
            Route::put('/{planning}', [NursingEmergencyPlanningController::class, 'update']);
            Route::delete('/{planning}', [NursingEmergencyPlanningController::class, 'destroy']);
        });
        // Bon Commend Routes - Specific routes MUST come before apiResource
        Route::prefix('bon-commends')->group(function () {
            // Static routes (no ID) must come first
            Route::get('/meta/products', [\App\Http\Controllers\Purchasing\BonCommendController::class, 'getProducts']);
            Route::get('/approval-thresholds', [\App\Http\Controllers\Purchasing\BonCommendController::class, 'getApprovalThresholds']);
            Route::get('/approval-thresholds/list', [\App\Http\Controllers\Purchasing\BonCommendController::class, 'getApprovalThresholdsList']);
            Route::post('/create-from-facture-proforma', [\App\Http\Controllers\Purchasing\BonCommendController::class, 'createFromFactureProforma']);
            Route::get('/{id}/pdf', [BonCommendController::class, 'downloadPdf']);
            Route::get('/{id}/pdf/preview', [BonCommendController::class, 'previewPdf']);
            Route::post('/{id}/pdf/save', [BonCommendController::class, 'generateAndSavePdf']);
            // Routes with IDs
            Route::get('/{id}/download', [\App\Http\Controllers\Purchasing\BonCommendController::class, 'download']);
            Route::put('/{id}/status', [\App\Http\Controllers\Purchasing\BonCommendController::class, 'updateStatus']);
            Route::post('/{id}/confirm-workflow', [\App\Http\Controllers\Purchasing\BonCommendController::class, 'confirmWorkflow']);
            Route::post('/{id}/submit-for-approval', [\App\Http\Controllers\Purchasing\BonCommendController::class, 'submitForApproval']);
            Route::get('/{id}/check-approval-needed', [\App\Http\Controllers\Purchasing\BonCommendController::class, 'checkApprovalNeeded']);

            // Attachment management
            Route::post('/{id}/attachments', [\App\Http\Controllers\Purchasing\BonCommendController::class, 'uploadAttachment']);
            Route::get('/{id}/attachments/{attachmentId}', [\App\Http\Controllers\Purchasing\BonCommendController::class, 'downloadAttachment']);
            Route::delete('/{id}/attachments/{attachmentId}', [\App\Http\Controllers\Purchasing\BonCommendController::class, 'deleteAttachment']);
            Route::get('/{id}/attachments/{index}/download', [\App\Http\Controllers\Purchasing\BonCommendController::class, 'downloadAttachment']);
        });
        Route::apiResource('bon-commends', \App\Http\Controllers\Purchasing\BonCommendController::class);

        // Consignment Reception Routes
        Route::prefix('consignments')->group(function () {
            // Static routes (no ID) must come first
            Route::get('/supplier/{supplierId}/stats', [ConsignmentReceptionController::class, 'supplierStats'])
                ->name('api.consignments.supplier-stats');

            // Routes with IDs
            Route::get('/{id}/uninvoiced-items', [ConsignmentReceptionController::class, 'uninvoicedItems'])
                ->name('api.consignments.uninvoiced-items');
            Route::post('/{id}/invoice', [ConsignmentReceptionController::class, 'createInvoice'])
                ->name('api.consignments.create-invoice');
            Route::get('/{id}/consumption-history', [ConsignmentReceptionController::class, 'consumptionHistory'])
                ->name('api.consignments.consumption-history');
            Route::post('/{id}/confirm', [ConsignmentReceptionController::class, 'confirm'])
                ->name('api.consignments.confirm');
        });
        Route::apiResource('consignments', ConsignmentReceptionController::class);

        // API Resource routes (index, store, show, update, destroy)

        // Approval Person Management Routes
        Route::prefix('approval-persons')->group(function () {
            Route::get('/', [ApprovalPersonController::class, 'index'])->name('api.approval-persons.index');
            Route::post('/', [ApprovalPersonController::class, 'store'])->name('api.approval-persons.store');
            Route::get('/for-amount', [ApprovalPersonController::class, 'getForAmount'])->name('api.approval-persons.for-amount');
            Route::get('/{approvalPerson}', [ApprovalPersonController::class, 'show'])->name('api.approval-persons.show');
            Route::put('/{approvalPerson}', [ApprovalPersonController::class, 'update'])->name('api.approval-persons.update');
            Route::delete('/{approvalPerson}', [ApprovalPersonController::class, 'destroy'])->name('api.approval-persons.destroy');
            Route::post('/{approvalPerson}/toggle-active', [ApprovalPersonController::class, 'toggleActive'])->name('api.approval-persons.toggle-active');
        });
        // Purchase Order Approval Routes
        Route::prefix('bon-commend-approvals')->group(function () {
            Route::get('/', [BonCommendApprovalController::class, 'index'])->name('api.bon-commend-approvals.index');
            Route::get('/my-pending', [BonCommendApprovalController::class, 'myPendingApprovals'])->name('api.bon-commend-approvals.my-pending');
            Route::get('/my-approvals', [BonCommendApprovalController::class, 'myApprovals'])->name('api.bon-commend-approvals.my-approvals');
            Route::get('/statistics', [BonCommendApprovalController::class, 'statistics'])->name('api.bon-commend-approvals.statistics');
            Route::get('/{approval}', [BonCommendApprovalController::class, 'show'])->name('api.bon-commend-approvals.show');
            Route::post('/request/{bonCommend}', [BonCommendApprovalController::class, 'requestApproval'])->name('api.bon-commend-approvals.request');
            Route::post('/{approval}/send-back', [BonCommendApprovalController::class, 'sendBack'])->name('api.bon-commend-approvals.send-back');
            Route::post('/{approval}/approve', [BonCommendApprovalController::class, 'approve'])->name('api.bon-commend-approvals.approve');
            Route::post('/{approval}/reject', [BonCommendApprovalController::class, 'reject'])->name('api.bon-commend-approvals.reject');
            Route::post('/{approval}/cancel', [BonCommendApprovalController::class, 'cancel'])->name('api.bon-commend-approvals.cancel');
        });

        // Bon Reception Routes
        Route::prefix('bon-receptions')->group(function () {
            // Helper endpoints (must come before parameterized routes)
            Route::get('/meta/bon-commends', [\App\Http\Controllers\Purchasing\BonReceptionController::class, 'getBonCommends']);
            Route::get('/meta/stats', [\App\Http\Controllers\Purchasing\BonReceptionController::class, 'getStats']);

            // Download/PDF routes (must come before parameterized routes)
            Route::get('/{id}/download', [\App\Http\Controllers\Purchasing\BonReceptionController::class, 'download']);
            Route::get('/{id}/export', [\App\Http\Controllers\Purchasing\BonReceptionController::class, 'export']);

            // New routes for surplus handling
            Route::post('/{id}/confirm', [\App\Http\Controllers\Purchasing\BonReceptionController::class, 'confirmReception']);
            Route::get('/{id}/surplus-preview', [\App\Http\Controllers\Purchasing\BonReceptionController::class, 'getSurplusPreview']);

            // Main CRUD operations
            Route::get('/', [\App\Http\Controllers\Purchasing\BonReceptionController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Purchasing\BonReceptionController::class, 'store']);
            Route::get('/{id}', [\App\Http\Controllers\Purchasing\BonReceptionController::class, 'show']);
            Route::put('/{id}', [\App\Http\Controllers\Purchasing\BonReceptionController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Purchasing\BonReceptionController::class, 'destroy']);

            // Special operations
            Route::post('/create-from-bon-commend', [\App\Http\Controllers\Purchasing\BonReceptionController::class, 'createFromBonCommend']);
            Route::post('/{id}/update-status', [\App\Http\Controllers\Purchasing\BonReceptionController::class, 'updateStatus']);
        });

        // Service Group Product Pricing Routes
        Route::prefix('service-groups/{serviceGroupId}/pricing')->group(function () {
            // Get all pricing for a service group
            Route::get('/', [ServiceGroupProductPricingController::class, 'indexByServiceGroup']);

            // Get/Update pricing for specific product
            Route::get('/product/{productId}', [ServiceGroupProductPricingController::class, 'show']);
            Route::post('/product/{productId}', [ServiceGroupProductPricingController::class, 'store']);

            // Price history
            Route::get('/product/{productId}/history', [ServiceGroupProductPricingController::class, 'history']);

            // Bulk operations
            Route::post('/bulk-update', [ServiceGroupProductPricingController::class, 'bulkUpdate']);

            // CSV Import/Export
            Route::get('/export', [ServiceGroupProductPricingController::class, 'export']);
            Route::post('/import', [ServiceGroupProductPricingController::class, 'import']);

            // Reports
            Route::get('/report', [ServiceGroupProductPricingController::class, 'report']);
        });

        // Service Group Pricing - Batch operations
        Route::prefix('service-group-pricing')->group(function () {
            Route::get('/product/{productId}/by-groups', [ServiceGroupProductPricingController::class, 'getProductPricingByGroups']);
            Route::post('/batch-update', [ServiceGroupProductPricingController::class, 'batchUpdatePricing']);
            Route::post('/update', [ServiceGroupProductPricingController::class, 'updateSinglePricing']);
        });

        // Delete specific pricing record
        Route::delete('/pricing/{pricingId}', [ServiceGroupProductPricingController::class, 'destroy']);

        // Purchasing Products routes (unified products from both stock and pharmacy)
        Route::prefix('purchasing')->group(function () {
            Route::get('products', [PurchasingProductController::class, 'index']); // Get combined products
            Route::post('products', [PurchasingProductController::class, 'store']); // Create in correct table
            Route::get('products/{id}', [PurchasingProductController::class, 'show']); // Get single product
            Route::put('products/{id}', [PurchasingProductController::class, 'update']); // Update product
            Route::delete('products/{id}', [PurchasingProductController::class, 'destroy']); // Delete product

            // Bon Entree Routes (moved inside purchasing prefix)
            Route::prefix('bon-entrees')->group(function () {
                // CRUD Routes
                Route::get('/', [BonEntreeController::class, 'index']);
                Route::post('/', [BonEntreeController::class, 'store']);

                // Meta routes (MUST BE BEFORE /{id} to avoid route conflict)
                Route::get('meta/stats', [BonEntreeController::class, 'getStats']);

                // Pricing Information (MUST BE BEFORE /{id} to avoid route conflict)
                Route::get('product/{productId}/pricing-info', [BonEntreeController::class, 'getProductPricingInfo']);

                // Special Routes (BEFORE /{id})
                Route::get('from-reception/{bonReceptionId}', [BonEntreeController::class, 'getFromBonReception']);

                // CRUD Routes with ID
                Route::get('/{id}', [BonEntreeController::class, 'show']);
                Route::put('/{id}', [BonEntreeController::class, 'update']);
                Route::delete('/{id}', [BonEntreeController::class, 'destroy']);

                // More Special Routes
                Route::get('/{id}/services-with-storages', [BonEntreeController::class, 'getServicesWithStorages']);
                Route::patch('/{id}/validate', [BonEntreeController::class, 'validate']);
                Route::patch('/{id}/transfer', [BonEntreeController::class, 'transfer']);
                Route::patch('/{id}/cancel', [BonEntreeController::class, 'cancel']);

                // Ticket/Label Generation
                Route::get('/{id}/generate-tickets', [BonEntreeController::class, 'generateTickets']);

                // Item Management
                Route::post('/{id}/items', [BonEntreeController::class, 'addItem']);
                Route::put('/{id}/items/{itemId}', [BonEntreeController::class, 'updateItem']);
                Route::delete('/{id}/items/{itemId}', [BonEntreeController::class, 'removeItem']);
            });
        });

        // Stock Products Paginated endpoint for infinite scroll
        Route::get('products-paginated', [\App\Http\Controllers\Stock\ProductController::class, 'paginated'])->withoutMiddleware(['auth:sanctum']);

        // Custom product routes
        Route::get('products/{id}/details', [\App\Http\Controllers\Stock\ProductController::class, 'getDetails']);
        Route::get('products/{productId}/stock-details', [\App\Http\Controllers\Stock\ProductController::class, 'getStockDetails']);
        Route::get('products/{productId}/settings', [\App\Http\Controllers\Stock\ProductController::class, 'getSettings']);
        Route::post('products/{productId}/settings', [\App\Http\Controllers\Stock\ProductController::class, 'saveSettings']);
        Route::post('products/bulk-update-approval', [\App\Http\Controllers\Stock\ProductController::class, 'bulkUpdateApproval'])->name('api.products.bulk-update-approval');
        Route::get('products/{productId}/supplier-pricing', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'getSupplierPricingForProduct']);
        Route::get('products/{productId}/supplier/{supplierId}/history', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'getDetailedSupplierHistory']);
        Route::get('products/{productId}/purchase-history', [\App\Http\Controllers\Stock\ProductController::class, 'getHistory']);

        // Pharmacy product pricing routes
        Route::get('pharmacy-products/{productId}/pricing-history', [\App\Http\Controllers\Purchasing\ServiceDemandPurchasingController::class, 'getPharmacyProductPricingHistory']);

        Route::apiResource('stockages', \App\Http\Controllers\Stock\StockageController::class);
        //  Custom inventory routes must come BEFORE the resource route
        Route::apiResource('inventory', \App\Http\Controllers\Inventory\InventoryController::class);
        Route::get('inventory/service-stock', [\App\Http\Controllers\Inventory\InventoryController::class, 'getServiceStock']);
        Route::post('inventory/{inventory}/adjust', [\App\Http\Controllers\Inventory\InventoryController::class, 'adjustStock']);
        Route::post('inventory/{inventory}/transfer', [\App\Http\Controllers\Inventory\InventoryController::class, 'transferStock']);

        // Inventory Audit routes
        Route::prefix('inventory-audits')->group(function () {
            Route::get('/my-audits', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'myAudits']);
            Route::post('/{inventoryAudit}/participants', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'addParticipant']);
            Route::delete('/{inventoryAudit}/participants', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'removeParticipant']);
            Route::post('/{inventoryAudit}/start', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'start']);
            Route::post('/{inventoryAudit}/complete', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'complete']);

            // Audit Items routes
            Route::get('/{inventoryAudit}/items', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'getItems']);
            Route::post('/{inventoryAudit}/items/bulk', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'bulkUpdateItems']);
            Route::get('/{inventoryAudit}/pdf', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'generatePdf']);

            // Reconciliation routes
            Route::get('/{inventoryAudit}/analyze-discrepancies', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'analyzeDiscrepancies']);
            Route::post('/{inventoryAudit}/assign-recount', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'assignRecount']);
            Route::post('/{inventoryAudit}/finalize-reconciliation', [\App\Http\Controllers\Inventory\InventoryAuditController::class, 'finalizeReconciliation']);
        });
        Route::apiResource('inventory-audits', \App\Http\Controllers\Inventory\InventoryAuditController::class);

        Route::apiResource('categories', \App\Http\Controllers\Stock\CategoryController::class);

        // Stock Movement routes
        Route::prefix('stock-movements')->group(function () {
            Route::get('/', [\App\Http\Controllers\Stock\StockMovementController::class, 'index']);
            Route::post('/create-draft', [\App\Http\Controllers\Stock\StockMovementController::class, 'createDraft']);
            Route::get('/drafts', [\App\Http\Controllers\Stock\StockMovementController::class, 'getDrafts']);
            Route::get('/pending-approvals', [\App\Http\Controllers\Stock\StockMovementController::class, 'getPendingApprovals']);
            Route::get('/suggestions', [\App\Http\Controllers\Stock\StockMovementController::class, 'getSuggestions']);
            Route::get('/stats', [\App\Http\Controllers\Stock\StockMovementController::class, 'getStats']);
            Route::get('/{movementId}', [\App\Http\Controllers\Stock\StockMovementController::class, 'show']);
            Route::delete('/{movementId}', [\App\Http\Controllers\Stock\StockMovementController::class, 'destroy']);
            Route::post('/{movementId}/send', [\App\Http\Controllers\Stock\StockMovementController::class, 'sendDraft']);
            Route::patch('/{movementId}/status', [\App\Http\Controllers\Stock\StockMovementController::class, 'updateStatus']);
            Route::get('/{movementId}/available-stock', [\App\Http\Controllers\Stock\StockMovementController::class, 'availableStock']);
            Route::get('/{movementId}/inventory/{productId}', [\App\Http\Controllers\Stock\StockMovementController::class, 'getProductInventory']);
            Route::post('/{movementId}/select-inventory', [\App\Http\Controllers\Stock\StockMovementController::class, 'selectInventory']);

            // Approval and rejection routes
            Route::post('/{movementId}/approve', [\App\Http\Controllers\Stock\StockMovementController::class, 'approveItems']);
            Route::post('/{movementId}/reject', [\App\Http\Controllers\Stock\StockMovementController::class, 'rejectItems']);

            // Transfer and delivery routes
            Route::post('/{movementId}/init-transfer', [\App\Http\Controllers\Stock\StockMovementController::class, 'initializeTransfer']);
            Route::post('/{movementId}/confirm-delivery', [\App\Http\Controllers\Stock\StockMovementController::class, 'confirmDelivery']);
            Route::post('/{movementId}/confirm-product', [\App\Http\Controllers\Stock\StockMovementController::class, 'confirmProduct']);

            // Validation workflow routes
            Route::post('/{movementId}/validate-quantities', [\App\Http\Controllers\Stock\StockMovementController::class, 'validateQuantities']);
            Route::post('/{movementId}/process-validation', [\App\Http\Controllers\Stock\StockMovementController::class, 'processValidation']);
            Route::post('/{movementId}/finalize-confirmation', [\App\Http\Controllers\Stock\StockMovementController::class, 'finalizeConfirmation']);

            // Item management routes
            Route::post('/{movementId}/items', [\App\Http\Controllers\Stock\StockMovementController::class, 'addItem']);
            Route::put('/{movementId}/items/{itemId}', [\App\Http\Controllers\Stock\StockMovementController::class, 'updateItem']);
            Route::delete('/{movementId}/items/{itemId}', [\App\Http\Controllers\Stock\StockMovementController::class, 'removeItem']);
        });

        // Stockage Tools (Location Management)
        Route::prefix('stockages/{stockage}/tools')->group(function () {
            Route::get('/', [\App\Http\Controllers\Stock\StockageToolController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Stock\StockageToolController::class, 'store']);
            Route::get('/{toolId}', [\App\Http\Controllers\Stock\StockageToolController::class, 'show']);
            Route::put('/{toolId}', [\App\Http\Controllers\Stock\StockageToolController::class, 'update']);
            Route::delete('/{toolId}', [\App\Http\Controllers\Stock\StockageToolController::class, 'destroy']);
        });
        // Helper routes for tool types and blocks
        Route::get('stockage-tools/types', [\App\Http\Controllers\Stock\StockageToolController::class, 'getToolTypes']);
        Route::get('stockage-tools/blocks', [\App\Http\Controllers\Stock\StockageToolController::class, 'getBlocks']);
        // Route for stockage managers removed — managers are no longer managed via StockageController
        // Stock Product Settings Routes (Web Routes)
        Route::prefix('stock')->group(function () {
            Route::get('product-settings/{serviceId}/{productName}/{productForme}', [\App\Http\Controllers\Stock\ServiceProductSettingController::class, 'show']);
            Route::post('product-settings', [\App\Http\Controllers\Stock\ServiceProductSettingController::class, 'store']);
            Route::put('product-settings/{serviceId}/{productName}/{productForme}', [\App\Http\Controllers\Stock\ServiceProductSettingController::class, 'update']);
            Route::delete('product-settings/{serviceId}/{productName}/{productForme}', [\App\Http\Controllers\Stock\ServiceProductSettingController::class, 'destroy']);
            Route::get('product-settings/{serviceId}', [\App\Http\Controllers\Stock\ServiceProductSettingController::class, 'getByService']);

            // Reserve Routes - No authorization required (accessible to all authenticated users)
            Route::get('reserves', [ReserveController::class, 'index']);
            Route::post('reserves', [ReserveController::class, 'store']);
            Route::get('reserves/{reserve}', [ReserveController::class, 'show']);
            Route::put('reserves/{reserve}', [ReserveController::class, 'update']);
            Route::patch('reserves/{reserve}', [ReserveController::class, 'update']);
            Route::delete('reserves/{reserve}', [ReserveController::class, 'destroy']);

            // Product Settings Routes (per product)
            Route::get('products/{product}/details', [\App\Http\Controllers\Stock\ProductController::class, 'getDetails']);
            Route::delete('products/bulk-delete', [\App\Http\Controllers\Stock\ProductController::class, 'bulkDelete']);
            Route::get('products/{productId}/settings', [\App\Http\Controllers\Stock\ProductGlobalSettingsController::class, 'index']);
            Route::post('products/{productId}/settings', [\App\Http\Controllers\Stock\ProductGlobalSettingsController::class, 'store']);
            Route::get('products/{productId}/settings/{key}', [\App\Http\Controllers\Stock\ProductGlobalSettingsController::class, 'show']);
            Route::put('products/{productId}/settings/{key}', [\App\Http\Controllers\Stock\ProductGlobalSettingsController::class, 'update']);
            Route::delete('products/{productId}/settings/{key}', [\App\Http\Controllers\Stock\ProductGlobalSettingsController::class, 'destroy']);

        });

        // Admission Routes
        Route::get('/admissions', [AdmissionController::class, 'index']);
        Route::post('/admissions', [AdmissionController::class, 'store']);
        Route::get('/admissions/statistics', [AdmissionController::class, 'statistics']);
        Route::get('/admissions/active', [AdmissionController::class, 'active']);
        Route::get('/admissions/next-file-number', [AdmissionController::class, 'getNextFileNumber']);
        Route::post('/admissions/{admission}/verify-file-number', [AdmissionController::class, 'verifyFileNumber']);
        Route::post('/admissions/{admission}/discharge', [AdmissionController::class, 'discharge']);
        Route::get('/admissions/{admission}', [AdmissionController::class, 'show']);
        Route::patch('/admissions/{admission}', [AdmissionController::class, 'update']);
        Route::delete('/admissions/{admission}', [AdmissionController::class, 'destroy']);

        // Admission Fiche Navette Routes
        Route::post('/admissions/{admission}/fiche-navette', [AdmissionController::class, 'getOrCreateFicheNavette']);
        Route::get('/admissions/{admission}/fiche-navette', [AdmissionController::class, 'getFicheNavette']);

        // Admission Procedure Routes
        Route::get('/admissions/{admission}/procedures', [AdmissionProcedureController::class, 'index']);
        Route::post('/admissions/{admission}/procedures', [AdmissionProcedureController::class, 'store']);
        Route::get('/admissions/{admission}/procedures/{procedure}', [AdmissionProcedureController::class, 'show']);
        Route::patch('/admissions/{admission}/procedures/{procedure}', [AdmissionProcedureController::class, 'update']);
        Route::post('/admissions/{admission}/procedures/{procedure}/complete', [AdmissionProcedureController::class, 'complete']);
        Route::post('/admissions/{admission}/procedures/{procedure}/cancel', [AdmissionProcedureController::class, 'cancel']);

        // Admission Document Routes
        Route::post('/admissions/{admission}/documents', [AdmissionDocumentController::class, 'store']);
        Route::get('/admissions/{admission}/documents/{document}/download', [AdmissionDocumentController::class, 'download']);
        Route::delete('/admissions/{admission}/documents/{document}', [AdmissionDocumentController::class, 'destroy']);
        Route::patch('/admissions/{admission}/documents/{document}/verify', [AdmissionDocumentController::class, 'verify']);

        // Admission Treatment Routes
        Route::get('/admissions/{admission}/treatments', [\App\Http\Controllers\Admission\AdmissionTreatmentController::class, 'index']);
        Route::post('/admissions/{admission}/treatments', [\App\Http\Controllers\Admission\AdmissionTreatmentController::class, 'store']);
        Route::get('/admissions/{admission}/treatments/{treatment}', [\App\Http\Controllers\Admission\AdmissionTreatmentController::class, 'show']);
        Route::patch('/admissions/{admission}/treatments/{treatment}', [\App\Http\Controllers\Admission\AdmissionTreatmentController::class, 'update']);
        Route::delete('/admissions/{admission}/treatments/{treatment}', [\App\Http\Controllers\Admission\AdmissionTreatmentController::class, 'destroy']);
    }); // End of /api group

    // The main application entry point for authenticated users
    Route::get('/{view}', [ApplicationController::class, '__invoke'])->where('view', '.*');
}); // End of authenticated middleware group
