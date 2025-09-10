<?php

use App\Http\Controllers\OpinionRequestController;
use Illuminate\Support\Facades\Route;

// Opinion Requests
Route::apiResource('/opinion-requests', OpinionRequestController::class);
Route::post('/opinion-requests/{id}/reply', [OpinionRequestController::class, 'reply']);