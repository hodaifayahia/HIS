<?php

use App\Http\Controllers\B2B\AgreementsController;
use App\Http\Controllers\B2B\AnnexController; // Assuming AnnexController is needed here for relation checks
use App\Http\Controllers\B2B\AvenantController;
use App\Http\Controllers\B2B\ConventionController;
use App\Http\Controllers\B2B\ConventionDetailController;
use App\Http\Controllers\B2B\ConvenctionDashborad;
use App\Http\Controllers\B2B\PrestationPricingController;
use App\Http\Controllers\PrescriptionController; // For getAnnexPrestation
use Illuminate\Support\Facades\Route;

// Conventions Dashboard
Route::get('/convention/dashboard', [ConvenctionDashborad::class, 'getDashboardData']);

// Conventions
Route::apiResource('/conventions', ConventionController::class);
Route::patch('/conventions/{conventionId}/activate', [ConventionController::class, 'activate']);
Route::patch('/conventions/{conventionId}/expire', [ConventionController::class, 'expire']);

// Agreement
Route::apiResource('/agreements', AgreementsController::class);

// Convention Detail Controller
Route::prefix('/convention/agreementdetails')->group(function () {
    Route::get('{conventionId}', [ConventionDetailController::class, 'getDetailsByConvention']);
    Route::put('{conventionId}/{detailId}', [ConventionDetailController::class, 'update']);
    Route::get('avenant/{avenantId}', [ConventionDetailController::class, 'getDetailsByAvenant']);
    Route::put('avenant/{avenantId}/{detailId}', [ConventionDetailController::class, 'update']);
});

// Prestation Pricing
Route::prefix('/prestation-pricings')->group(function () {
    Route::get('/', [PrestationPricingController::class, 'index']);
    Route::post('/', [PrestationPricingController::class, 'store']);
    Route::put('/{id}', [PrestationPricingController::class, 'update']);
    Route::patch('/{id}', [PrestationPricingController::class, 'update']);
    Route::delete('/{id}', [PrestationPricingController::class, 'destroy']);
    Route::post('/bulk-delete', [PrestationPricingController::class, 'bulkDelete']);

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

// Other B2B related routes (verify if these fit better elsewhere)
Route::get('/prestation/annex/price', [PrescriptionController::class, 'getAnnexPrestation']);
Route::post('/organismes/settings', [ConventionController::class, 'activateConvenation']); // This one seems misplaced, consider if it's truly B2B or CRM.