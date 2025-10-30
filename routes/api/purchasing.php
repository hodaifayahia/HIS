<?php

use App\Http\Controllers\Api\ApprovalPersonController;
use App\Http\Controllers\Api\BonCommendApprovalController;
use App\Http\Controllers\Api\InventoryAuditProductController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ProductHistoryController;
use App\Http\Controllers\Purchasing\PurchasingProductController;
use Illuminate\Support\Facades\Route;

// Purchasing Products Routes - Combined from both stock and pharmacy tables
Route::prefix('products')->group(function () {
    // Index - Get all products from both tables
    Route::get('/', [PurchasingProductController::class, 'index'])->name('api.purchasing.products.index');
    
    // Store - Create product (routes to correct table based on is_clinic flag)
    Route::post('/', [PurchasingProductController::class, 'store'])->name('api.purchasing.products.store');
    
    // Show - Get specific product from either table
    Route::get('/{id}', [PurchasingProductController::class, 'show'])->name('api.purchasing.products.show');
    
    // Update - Update product in correct table
    Route::put('/{id}', [PurchasingProductController::class, 'update'])->name('api.purchasing.products.update');
    
    // Delete - Delete product from correct table
    Route::delete('/{id}', [PurchasingProductController::class, 'destroy'])->name('api.purchasing.products.destroy');
    
    // Product History API - Put this AFTER resource routes but in same prefix to avoid conflicts
    Route::get('/{productId}/history', [ProductHistoryController::class, 'getProductHistory'])
        ->name('api.purchasing.products.history');
});

// Attachment Management Routes
Route::prefix('attachments')->group(function () {
    Route::post('upload', [AttachmentController::class, 'upload'])->name('attachments.upload');
    Route::get('download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::get('view', [AttachmentController::class, 'view'])->name('attachments.view');
    Route::delete('delete', [AttachmentController::class, 'delete'])->name('attachments.delete');
    Route::get('list', [AttachmentController::class, 'list'])->name('attachments.list');
});

// Approval Person Management Routes
Route::prefix('approval-persons')->group(function () {
    Route::get('/', [ApprovalPersonController::class, 'index'])->name('api.approval-persons.index');
    Route::post('/', [ApprovalPersonController::class, 'store'])->name('api.approval-persons.store');
    Route::get('/for-amount', [ApprovalPersonController::class, 'getForAmount'])->name('api.approval-persons.for-amount');
    Route::get('/{approvalPerson}', [ApprovalPersonController::class, 'show'])->name('api.approval-persons.show');
    Route::put('/{approvalPerson}', [ApprovalPersonController::class, 'update'])->name('api.approval-persons.update');
    Route::delete('/{approvalPerson}', [ApprovalPersonController::class, 'destroy'])->name('api.approval-persons.destroy');
    Route::post('/{approvalPerson}/toggle-active', [ApprovalPersonController::class, 'toggleActive'])->name('api.approval-persons.toggle-active');
});

// Bon Commend Approval Routes
Route::prefix('bon-commend-approvals')->group(function () {
    Route::get('/', [BonCommendApprovalController::class, 'index'])->name('api.bon-commend-approvals.index');
    Route::get('/my-pending', [BonCommendApprovalController::class, 'myPendingApprovals'])->name('api.bon-commend-approvals.my-pending');
    Route::get('/statistics', [BonCommendApprovalController::class, 'statistics'])->name('api.bon-commend-approvals.statistics');
    Route::get('/{approval}', [BonCommendApprovalController::class, 'show'])->name('api.bon-commend-approvals.show');
    Route::post('/{approval}/approve', [BonCommendApprovalController::class, 'approve'])->name('api.bon-commend-approvals.approve');
    Route::post('/{approval}/reject', [BonCommendApprovalController::class, 'reject'])->name('api.bon-commend-approvals.reject');
    Route::post('/{approval}/send-back', [BonCommendApprovalController::class, 'sendBack'])->name('api.bon-commend-approvals.send-back');
    Route::post('/{approval}/cancel', [BonCommendApprovalController::class, 'cancel'])->name('api.bon-commend-approvals.cancel');
});

// Bon Commend - Request Approval
Route::post('bon-commends/{bonCommend}/request-approval', [BonCommendApprovalController::class, 'requestApproval'])
    ->name('api.bon-commends.request-approval');

// Inventory Audit Routes
Route::prefix('inventory-audit')->group(function () {
    Route::get('/products', [InventoryAuditProductController::class, 'getProductsForAudit'])->name('api.inventory-audit.products');
    Route::post('/save', [InventoryAuditProductController::class, 'saveAudit'])->name('api.inventory-audit.save');
    Route::get('/template', [InventoryAuditProductController::class, 'downloadTemplate'])->name('api.inventory-audit.template');
    Route::get('/export', [InventoryAuditProductController::class, 'exportToExcel'])->name('api.inventory-audit.export');
    Route::post('/import', [InventoryAuditProductController::class, 'importFromExcel'])->name('api.inventory-audit.import');
    Route::post('/report', [InventoryAuditProductController::class, 'generatePdfReport'])->name('api.inventory-audit.report');
    Route::get('/history', [InventoryAuditProductController::class, 'getAuditHistory'])->name('api.inventory-audit.history');
});
