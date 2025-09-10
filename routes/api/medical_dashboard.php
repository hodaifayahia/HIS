<?php

use App\Http\Controllers\DashboradController;
use Illuminate\Support\Facades\Route;

// Medical Dashboard
Route::get('/medical-dashboard', [DashboradController::class, 'index']);