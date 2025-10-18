<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Reception\ficheNavetteController;
use App\Http\Controllers\manager\TransactionBankRequestController;
use App\Http\Controllers\CONFIGURATION\UserCaisseApprovalController;
use App\Http\Controllers\Caisse\FinancialTransactionController;
use App\Http\Controllers\Coffre\CoffreTransactionController;
use App\Http\Controllers\RequestTransactionApprovalController;

// This file is for any API routes that don't fit into the more specific categories.
// Review your original web.php to see if anything was left out.
// As per your provided routes, there might not be much left for this file.
// If you had any simple, standalone API routes, they would go here.

// Return fiche prestations filtered by authenticated user's specializations
Route::get('reception/fiche-navette/{id}/filtered-prestations', [ficheNavetteController::class, 'getPrestationsForFicheByAuthenticatedUser']);

// Financial Transaction Routes
Route::group(function () {
    Route::prefix('financial-transactions')->group(function () {
        Route::get('/', [FinancialTransactionController::class, 'index']);
        Route::post('/', [FinancialTransactionController::class, 'store']);
        Route::get('/{financialTransaction}', [FinancialTransactionController::class, 'show']);
        Route::put('/{financialTransaction}', [FinancialTransactionController::class, 'update']);
        Route::delete('/{financialTransaction}', [FinancialTransactionController::class, 'destroy']);
        
        // Specialized endpoints
        Route::post('/handle-overpayment', [FinancialTransactionController::class, 'handleOverpayment']);
        Route::post('/process-refund', [FinancialTransactionController::class, 'processRefund']);
        Route::post('/bulk-payment', [FinancialTransactionController::class, 'bulkPayment']);
        Route::get('/stats', [FinancialTransactionController::class, 'stats']);
        Route::get('/refundable', [FinancialTransactionController::class, 'getRefundableTransactions']);
        Route::get('/prestations-with-dependencies', [FinancialTransactionController::class, 'getPrestationsWithDependencies']);
        Route::get('/patient-prestations', [FinancialTransactionController::class, 'getPatientPrestations']);
        Route::get('/daily-summary', [FinancialTransactionController::class, 'dailySummary']);
    });
    
    // Alternative endpoint for fiche navette transactions
    Route::get('financial-transactions-by-fiche-navette', [FinancialTransactionController::class, 'getByFicheNavette']);
});

// Transaction Bank Request Routes
Route::group(function () {
    Route::prefix('transaction-bank-requests')->group(function () {
        Route::get('/', [TransactionBankRequestController::class, 'index']);
        Route::post('/', [TransactionBankRequestController::class, 'store']);
        Route::patch('/{transactionBankRequest}/status', [TransactionBankRequestController::class, 'updateStatus']);
        Route::post('/{transactionBankRequest}/update-attachment', [TransactionBankRequestController::class, 'updateAttachment']);
        Route::get('/pending-approvals', [TransactionBankRequestController::class, 'getPendingApprovals']);
    });
    
    // User Caisse Approval Routes
    Route::prefix('user-caisse-approval')->group(function () {
        Route::get('/approvers', [UserCaisseApprovalController::class, 'getApprovers']);
    });
});

// Coffre Transaction Routes
Route::group(function () {
    Route::prefix('coffre-transactions')->group(function () {
        Route::get('/', [CoffreTransactionController::class, 'index']);
        Route::post('/', [CoffreTransactionController::class, 'store']);
        Route::get('/{coffreTransaction}', [CoffreTransactionController::class, 'show']);
        Route::put('/{coffreTransaction}', [CoffreTransactionController::class, 'update']);
        Route::delete('/{coffreTransaction}', [CoffreTransactionController::class, 'destroy']);
        
        // Helper endpoints
        Route::get('/types/all', [CoffreTransactionController::class, 'transactionTypes']);
        Route::get('/coffres/all', [CoffreTransactionController::class, 'coffres']);
        Route::get('/users/all', [CoffreTransactionController::class, 'users']);
    });
    
    // Request Transaction Approval Routes
    Route::prefix('request-transaction-approvals')->group(function () {
        Route::get('/', [RequestTransactionApprovalController::class, 'index']);
        Route::patch('/{requestTransactionApproval}/approve', [RequestTransactionApprovalController::class, 'approve']);
        Route::patch('/{requestTransactionApproval}/reject', [RequestTransactionApprovalController::class, 'reject']);
    });
});