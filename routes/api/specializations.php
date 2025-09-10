<?php

use App\Http\Controllers\specializationsController;
use Illuminate\Support\Facades\Route;

// Specializations Routes
Route::get('/specializations', [specializationsController::class, 'index']);
Route::post('/specializations', [specializationsController::class, 'store']);
Route::put('/specializations/{id}', [specializationsController::class, 'update']);
Route::delete('/specializations/{id}', [specializationsController::class, 'destroy']);