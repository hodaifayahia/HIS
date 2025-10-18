<?php

use App\Http\Controllers\FolderController;
use Illuminate\Support\Facades\Route;

// Folders
Route::apiResource('/folders', FolderController::class);
Route::get('folders/{folder}/templates', [FolderController::class, 'getTemplates']);
Route::get('folders/search', [FolderController::class, 'search']);