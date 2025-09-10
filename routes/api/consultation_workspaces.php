<?php

use App\Http\Controllers\ConsultationworkspacesController;
use Illuminate\Support\Facades\Route;

// Consultation Workspaces
Route::apiResource('/consultationworkspaces', ConsultationworkspacesController::class);
Route::get('/consultationworkspaces/search', [ConsultationworkspacesController::class, 'search']);
Route::get('/details/consultationworkspaces', [ConsultationworkspacesController::class, 'getworkspaceDetails']);
Route::post('/details/consultationworkspaces', [ConsultationworkspacesController::class, 'storeworkDetails']);
Route::delete('/details/consultationworkspaces', [ConsultationworkspacesController::class, 'DeleteworkspaceDetails']);