<?php

use App\Http\Controllers\B2B\AnnexController;
use Illuminate\Support\Facades\Route;

// Annexes
Route::get('/annex/contract/{contractId}', [AnnexController::class, 'getByContract']);
Route::apiResource('annex', AnnexController::class);
// This route seems like a typo in your original. Corrected to /{id}
Route::get('/annex/{id}/check-relations', [AnnexController::class, 'checkRelations']);
Route::post('/annex/{contractId}', [AnnexController::class, 'storeWithContract']);