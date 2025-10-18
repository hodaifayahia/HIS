<?php

use App\Http\Controllers\Api\ApprovalPersonController;
use App\Http\Controllers\Api\BonCommendApprovalController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ProductHistoryController;
use Illuminate\Support\Facades\Route;

// Product History API - Put this BEFORE resource routes to avoid conflicts
Route::get('products/{productId}/history', [ProductHistoryController::class, 'getProductHistory'])
    ->name('api.purchasing.products.history');

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
