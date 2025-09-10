<?php

use App\Http\Controllers\AttributeController;
use App\Http\Controllers\PlaceholderController;
use Illuminate\Support\Facades\Route;

// Placeholders and Attributes
Route::resource('placeholders', PlaceholderController::class)->only(['index', 'store', 'update', 'destroy']);
Route::post('placeholders/consultation-attributes/save', [PlaceholderController::class, 'saveConsultationAttributes']);
Route::get('placeholders/consultation-attributes/search-values', [AttributeController::class, 'searchAttributeValues']);
Route::get('/placeholders/consultation/{appointmentid}/attributes', [PlaceholderController::class, 'getConsultationPlaceholderAttributes']);

// Attributes
Route::post('/attributes', [AttributeController::class, 'store']);
Route::get('/attributes/{id}', [AttributeController::class, 'index']);
Route::put('/attributes/{id}', [AttributeController::class, 'update']);
Route::delete('/attributes/{id}', [AttributeController::class, 'destroy']);
Route::get('/attributes/search', [AttributeController::class, 'search']);
Route::get('/attributes/metadata', [AttributeController::class, 'getMetadata']);