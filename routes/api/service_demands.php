<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceDemandPurchasingController;
use App\Http\Controllers\FactureProformaController;
use App\Http\Controllers\BonCommendController;

// Service Demand Purchasing Routes
Route::prefix('service-demands')->group(function () {
    // Helper endpoints (must come before parameterized routes)
    Route::get('/meta/services', [ServiceDemandPurchasingController::class, 'getServices']);
    Route::get('/meta/products', [ServiceDemandPurchasingController::class, 'getProducts']);
    Route::get('/meta/stats', [ServiceDemandPurchasingController::class, 'getStats']);
    Route::get('/suggestions', [ServiceDemandPurchasingController::class, 'getSuggestions']);
    
    // Main CRUD operations
    Route::get('/', [ServiceDemandPurchasingController::class, 'index']);
    Route::post('/', [ServiceDemandPurchasingController::class, 'store']);
    Route::get('/{id}', [ServiceDemandPurchasingController::class, 'show']);
    Route::put('/{id}', [ServiceDemandPurchasingController::class, 'update']);
    Route::delete('/{id}', [ServiceDemandPurchasingController::class, 'destroy']);
    
    // Item management
    Route::post('/{id}/items', [ServiceDemandPurchasingController::class, 'addItem']);
    Route::put('/{id}/items/{itemId}', [ServiceDemandPurchasingController::class, 'updateItem']);
    Route::delete('/{id}/items/{itemId}', [ServiceDemandPurchasingController::class, 'removeItem']);
    
    // Status management
    Route::post('/{id}/send', [ServiceDemandPurchasingController::class, 'send']);
    
    // Workflow tracking
    Route::post('/{id}/add-note', [ServiceDemandPurchasingController::class, 'addWorkflowNote']);
});

// Facture Proforma Routes
Route::prefix('facture-proformas')->group(function () {
    // Helper endpoints (must come before parameterized routes)
    Route::get('/meta/service-demands', [FactureProformaController::class, 'getServiceDemands']);
    Route::get('/meta/suppliers', [FactureProformaController::class, 'getSuppliers']);
    Route::get('/meta/products', [FactureProformaController::class, 'getProducts']);
    Route::get('/meta/stats', [FactureProformaController::class, 'getStats']);
    
    // Main CRUD operations
    Route::get('/', [FactureProformaController::class, 'index']);
    Route::post('/', [FactureProformaController::class, 'store']);
    Route::get('/{id}', [FactureProformaController::class, 'show']);
    Route::put('/{id}', [FactureProformaController::class, 'update']);
    Route::delete('/{id}', [FactureProformaController::class, 'destroy']);
    
    // Special operations
    Route::post('/create-from-demands', [FactureProformaController::class, 'createFromServiceDemands']);
    Route::post('/{id}/send', [FactureProformaController::class, 'send']);
    Route::get('/{id}/download', [FactureProformaController::class, 'download']);
    Route::post('/{id}/mark-as-paid', [FactureProformaController::class, 'markAsPaid']);
    
    // Product detail management
    Route::get('/{id}/products', [FactureProformaController::class, 'getProformaProducts']);
    Route::put('/{id}/products/{productId}', [FactureProformaController::class, 'updateProductQuantity']);
    
    // Attachment management
    Route::post('/{id}/attachments', [FactureProformaController::class, 'uploadAttachment']);
    Route::get('/{id}/attachments/{attachmentId}', [FactureProformaController::class, 'downloadAttachment']);
    Route::delete('/{id}/attachments/{attachmentId}', [FactureProformaController::class, 'deleteAttachment']);
});

// Bon Commend Routes
Route::prefix('bon-commends')->group(function () {
    // Helper endpoints (must come before parameterized routes)
    Route::get('/meta/facture-proformas', [BonCommendController::class, 'getFactureProformas']);
    Route::get('/meta/suppliers', [BonCommendController::class, 'getSuppliers']);
    Route::get('/meta/products', [BonCommendController::class, 'getProducts']);
    Route::get('/meta/stats', [BonCommendController::class, 'getStats']);
    
    // Main CRUD operations
    Route::get('/', [BonCommendController::class, 'index']);
    Route::post('/', [BonCommendController::class, 'store']);
    Route::get('/{id}', [BonCommendController::class, 'show']);
    Route::put('/{id}', [BonCommendController::class, 'update']);
    Route::delete('/{id}', [BonCommendController::class, 'destroy']);
    
    // Special operations
    Route::post('/create-from-facture-proforma', [BonCommendController::class, 'createFromFactureProforma']);
    Route::post('/{id}/update-status', [BonCommendController::class, 'updateStatus']);
    Route::get('/{id}/download', [BonCommendController::class, 'download']);
    Route::post('/{id}/send', [BonCommendController::class, 'send']);
    
    // Item detail management
    Route::get('/{id}/items', [BonCommendController::class, 'getBonCommendItems']);
    Route::put('/{id}/items/{itemId}', [BonCommendController::class, 'updateItemQuantity']);
    
    // Attachment management
    Route::post('/{id}/attachments', [BonCommendController::class, 'uploadAttachment']);
    Route::get('/{id}/attachments/{attachmentId}', [BonCommendController::class, 'downloadAttachment']);
    Route::delete('/{id}/attachments/{attachmentId}', [BonCommendController::class, 'deleteAttachment']);
});

// Fournisseurs Routes (for compatibility)
Route::prefix('fournisseurs')->group(function () {
    Route::get('/', [FactureProformaController::class, 'getSuppliers']);
});