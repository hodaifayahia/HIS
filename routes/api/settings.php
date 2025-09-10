<?php

use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

// Setting Routes
Route::get('/setting/user', [SettingController::class, 'index']);
Route::put('/setting/user', [SettingController::class, 'update']);
Route::put('/setting/password', [SettingController::class, 'updatePassword']);