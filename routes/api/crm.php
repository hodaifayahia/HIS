<?php

use App\Http\Controllers\CRM\OrganismeContactController;
use App\Http\Controllers\CRM\OrganismeController;
use Illuminate\Support\Facades\Route;

// Organism
Route::get('/organismes/settings', [OrganismeController::class, 'OrganismesSettings']);
Route::apiResource('/organismes', OrganismeController::class);
Route::apiResource('/organisme-contacts', OrganismeContactController::class);