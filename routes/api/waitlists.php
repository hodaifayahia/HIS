<?php

use App\Http\Controllers\WaitListController;
use Illuminate\Support\Facades\Route;

// Waitlist Routes
Route::post('/waitlists', [WaitListController::class, 'store']);
Route::post('/waitlists/count/{doctorid}', [WaitListController::class, 'countForYouAndNotForYou']);
Route::put('/waitlists/{waitlist}', [WaitListController::class, 'update']);
Route::get('/waitlists', [WaitListController::class, 'index']);
Route::get('/waitlists/ForDoctor', [WaitListController::class, 'GetForDoctor']);
Route::get('/waitlists/search', [WaitListController::class, 'search']);
Route::get('/waitlists/filter', [WaitlistController::class, 'filter']);
Route::post('/waitlists/{waitlist}/add-to-appointments', [WaitListController::class, 'AddPaitentToAppointments']);
Route::post('/waitlists/{waitlistid}/move-to-end', [WaitListController::class, 'moveToend']);
Route::delete('/waitlists/{waitlist}', [WaitListController::class, 'destroy']);
Route::patch('/waitlists/{waitlist}/importance', [WaitlistController::class, 'updateImportance']);
Route::get('/waitlist/empty', [WaitListController::class, 'isempty']);