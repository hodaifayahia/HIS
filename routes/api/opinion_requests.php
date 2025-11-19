<?php

use App\Http\Controllers\OpinionRequestController;
use Illuminate\Support\Facades\Route;

// Opinion Requests - Custom routes first
Route::withoutMiddleware(['auth', 'auth:sanctum'])->group(function () {
    Route::get('/opinion-requests/pending-opinion-requests-count/{doctorId}', [OpinionRequestController::class, 'pendingOpinionRequestsCount']);
});
Route::post('/opinion-requests/{id}/reply', [OpinionRequestController::class, 'reply']);
Route::apiResource('/opinion-requests', OpinionRequestController::class);
