<?php

use App\Http\Controllers\ImportanceEnumController;
use Illuminate\Support\Facades\Route;

// Importance Enum
Route::get('/importance-enum', [ImportanceEnumController::class, 'index']);