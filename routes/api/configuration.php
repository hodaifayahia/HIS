<?php

use App\Http\Controllers\CONFIGURATION\ModalityTypeController;
use App\Http\Controllers\CONFIGURATION\PrestationController;
use App\Http\Controllers\CONFIGURATION\RemiseController;
use App\Http\Controllers\CONFIGURATION\ServiceController;
use App\Http\Controllers\CONFIGURATION\UserPaymentMethodController;
use App\Http\Controllers\CONFIGURATION\PermissionController;
use App\Http\Controllers\CONFIGURATION\UserCaisseApprovalController;
use Illuminate\Support\Facades\Route;

// Configuration Services
Route::apiResource('/services', ServiceController::class);
Route::apiResource('/modality-types', ModalityTypeController::class);
Route::apiResource('/prestation', PrestationController::class);

Route::apiResource('/user-payment-methods', UserPaymentMethodController::class);
Route::apiResource('/remise', RemiseController::class);
Route::get('/payment-methods', [UserPaymentMethodController::class, 'getPaymentMethods']);

// Prestation specific routes
Route::prefix('/prestation')->group(function () {
    Route::post('/import', [PrestationController::class, 'import']);
    Route::get('/filter-options', [PrestationController::class, 'getFilterOptions']);
    Route::get('/statistics', [PrestationController::class, 'getStatistics']);
    Route::patch('/{id}/toggle-status', [PrestationController::class, 'toggleStatus']);
});

// Permission Management Routes
Route::prefix('/permissions')->group(function () {
    Route::get('/', [PermissionController::class, 'index']);
    Route::post('/', [PermissionController::class, 'store']);
    Route::get('/{permission}', [PermissionController::class, 'show']);
    Route::put('/{permission}', [PermissionController::class, 'update']);
    Route::delete('/{permission}', [PermissionController::class, 'destroy']);

    // Permission assignment routes
    Route::post('/assign', [PermissionController::class, 'assignToUser']);
    Route::post('/revoke', [PermissionController::class, 'revokeFromUser']);

    // User permission routes
    Route::get('/user/{user}', [PermissionController::class, 'getUserPermissions']);
    Route::post('/check', [PermissionController::class, 'checkUserPermission']);
    Route::get('/users-with-permission', [PermissionController::class, 'getUsersWithPermission']);
});

// Caisse Approval Routes (backward compatibility)
Route::prefix('/caisse-approval')->group(function () {
    Route::get('/users', [UserCaisseApprovalController::class, 'index']);
    Route::post('/grant', [UserCaisseApprovalController::class, 'store']);
    Route::delete('/revoke/{user}', [UserCaisseApprovalController::class, 'destroy']);
    Route::get('/check-auth', [UserCaisseApprovalController::class, 'checkAuth']);
    Route::get('/approvers', [UserCaisseApprovalController::class, 'getApprovers']);
    Route::post('/set-permission-name', [UserCaisseApprovalController::class, 'setPermissionName']);
    Route::get('/permission-name', [UserCaisseApprovalController::class, 'getPermissionName']);
});