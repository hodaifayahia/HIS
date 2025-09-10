<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// User Routes
Route::get('/users', [UserController::class, 'index']);
Route::get('/users/receptionist', [UserController::class, 'GetReceptionists']);
Route::post('/users', [UserController::class, 'store']);
Route::delete('/users', [UserController::class, 'bulkDelete'])->name('users.bulkDelete');
Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
Route::put('/users/{userid}', [UserController::class, 'update']);
Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
Route::patch('/users/{userid}/change-role', [UserController::class, 'ChangeRole'])->name('users.ChangeRole');
Route::get('/loginuser', [UserController::class, 'getCurrentUser']);
Route::get('/role', [UserController::class, 'role']);

Route::apiResource('/roles', RoleController::class);