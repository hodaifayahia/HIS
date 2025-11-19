<?php

use App\Http\Controllers\ImportanceEnumController;
use Illuminate\Support\Facades\Route;

// Route for getting importance enum data (public access)
Route::withoutMiddleware(['auth', 'auth:sanctum'])->group(function () {
    Route::get('/importance-enum', [ImportanceEnumController::class, 'index']);
});
