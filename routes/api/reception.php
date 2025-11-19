<?php

use App\Http\Controllers\Reception\ficheNavetteItemController;
use App\Http\Controllers\Reception\PrestationPackageDoctorAssignmentController;
use Illuminate\Support\Facades\Route;

// Fiche Navette Routes
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

    // Fiche Navette Item Routes
    Route::prefix('/fiche-navette/{ficheNavetteId}/items')->group(function () {
        Route::post('/', [ficheNavetteItemController::class, 'store']);
        Route::get('/', [ficheNavetteItemController::class, 'index']);
        Route::get('/grouped-by-insured', [ficheNavetteItemController::class, 'getGroupedByInsured']);
        Route::put('/{itemId}', [ficheNavetteItemController::class, 'update']);
        Route::delete('/{itemId}', [ficheNavetteItemController::class, 'destroy']);
    });

    // Convention Prescription Routes
    Route::post('/fiche-navette/{ficheNavetteId}/convention-prescription', 
        [ficheNavetteItemController::class, 'storeConventionPrescription']);

    // Prestation Routes (grouped by convention)
    Route::get('/prestations/convention/{conventionId}', 
        [ficheNavetteItemController::class, 'getPrestationsByConvention']);
    
    Route::post('/prestations/with-convention-pricing', 
        [ficheNavetteItemController::class, 'getPrestationsWithConventionPricing']);

    // Package Routes
    Route::get('/packages/{packageId}/prestations', 
        [ficheNavetteItemController::class, 'getPrestationsByPackage']);

    // File Management Routes
    Route::post('/upload-convention-files', 
        [ficheNavetteItemController::class, 'uploadConventionFiles']);
    
    Route::get('/download-file/{fileId}', 
        [ficheNavetteItemController::class, 'downloadFile']);
    
    Route::get('/download-convention-file/{fileId}', 
        [ficheNavetteItemController::class, 'downloadConventionFile']);
    
    Route::get('/view-file/{fileId}', 
        [ficheNavetteItemController::class, 'viewFile']);

    // Package Conversion Routes
    Route::post('/fiche-navette/{ficheNavetteId}/convert-to-package', 
        [ficheNavetteItemController::class, 'convertToPackage']);
    
    Route::post('/fiche-navette/{ficheNavetteId}/create-package', 
        [ficheNavetteItemController::class, 'createPackageWithDoctorValidation']);

    // Additional Routes
    Route::post('/fiche-navette/{ficheNavetteId}/add-separate', 
        [ficheNavetteItemController::class, 'addSeparatePrestations']);

    // Patient Conventions Routes
    Route::get('/patients/{patientId}/conventions', 
        [ficheNavetteItemController::class, 'getPatientConventions']);

    // Dependency Routes
    Route::put('/dependencies/{dependencyId}', 
        [ficheNavetteItemController::class, 'updateDependency']);
    
    Route::delete('/dependencies/{dependencyId}', 
        [ficheNavetteItemController::class, 'removeDependency']);
});
