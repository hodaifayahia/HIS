<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

// Import/Export
Route::post('/import/users', [ImportController::class, 'ImportUsers']);
Route::get('/export/users', [ExportController::class, 'ExportUsers']);
Route::post('/import/Patients', [ImportController::class, 'ImportPatients']);
Route::get('/export/Patients', [ExportController::class, 'ExportPatients']);
Route::post('/import/placeholders', [ImportController::class, 'Importplaceholders']);
Route::get('/export/placeholders', [ExportController::class, 'Exportplaceholders']);
Route::post('/import/attributes', [ImportController::class, 'ImportAttributes']);
Route::get('/export/attributes', [ExportController::class, 'ExportAttributes']);
Route::post('/import/appointment/{doctorid}', [ImportController::class, 'ImportAppointment']);
Route::get('/export/appointment', [ExportController::class, 'ExportAppointment']);