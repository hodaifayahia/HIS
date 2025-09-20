<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Stock Management API Routes
Route::apiResource('products', \App\Http\Controllers\Stock\ProductController::class);

// Custom product routes
Route::get('products/{id}/details', [\App\Http\Controllers\Stock\ProductController::class, 'getDetails']);
Route::get('products/{productId}/settings', [\App\Http\Controllers\Stock\ProductController::class, 'getSettings']);
Route::post('products/{productId}/settings', [\App\Http\Controllers\Stock\ProductController::class, 'saveSettings']);

Route::apiResource('stockages', \App\Http\Controllers\Stock\StockageController::class);

// Custom inventory routes
Route::get('inventory/service-stock', [\App\Http\Controllers\Stock\InventoryController::class, 'getServiceStock']);
Route::post('inventory/{inventory}/adjust', [\App\Http\Controllers\Stock\InventoryController::class, 'adjustStock']);
Route::post('inventory/{inventory}/transfer', [\App\Http\Controllers\Stock\InventoryController::class, 'transferStock']);
Route::apiResource('inventory', \App\Http\Controllers\Stock\InventoryController::class);
Route::apiResource('categories', \App\Http\Controllers\Stock\CategoryController::class);

// Stock Movement routes
Route::prefix('stock-movements')->group(function () {
    Route::get('/', [\App\Http\Controllers\Stock\StockMovementController::class, 'index']);
    Route::post('/create-draft', [\App\Http\Controllers\Stock\StockMovementController::class, 'createDraft']);
    Route::get('/drafts', [\App\Http\Controllers\Stock\StockMovementController::class, 'getDrafts']);
    Route::get('/pending-approvals', [\App\Http\Controllers\Stock\StockMovementController::class, 'getPendingApprovals']);
    Route::get('/suggestions', [\App\Http\Controllers\Stock\StockMovementController::class, 'getSuggestions']);
    Route::get('/stats', [\App\Http\Controllers\Stock\StockMovementController::class, 'getStats']);
    Route::get('/{movementId}', [\App\Http\Controllers\Stock\StockMovementController::class, 'show']);
    Route::delete('/{movementId}', [\App\Http\Controllers\Stock\StockMovementController::class, 'destroy']);
    Route::post('/{movementId}/send', [\App\Http\Controllers\Stock\StockMovementController::class, 'sendDraft']);
    Route::patch('/{movementId}/status', [\App\Http\Controllers\Stock\StockMovementController::class, 'updateStatus']);
    Route::get('/{movementId}/available-stock', [\App\Http\Controllers\Stock\StockMovementController::class, 'availableStock']);
    Route::get('/{movementId}/inventory/{productId}', [\App\Http\Controllers\Stock\StockMovementController::class, 'getProductInventory']);
    Route::post('/{movementId}/select-inventory', [\App\Http\Controllers\Stock\StockMovementController::class, 'selectInventory']);
    
    // Approval management
    Route::post('/{movementId}/approve', [\App\Http\Controllers\Stock\StockMovementController::class, 'approveItems']);
    Route::post('/{movementId}/reject', [\App\Http\Controllers\Stock\StockMovementController::class, 'rejectItems']);
    
    // Item management
    Route::post('/{movementId}/items', [\App\Http\Controllers\Stock\StockMovementController::class, 'addItem']);
    Route::put('/{movementId}/items/{itemId}', [\App\Http\Controllers\Stock\StockMovementController::class, 'updateItem']);
    Route::delete('/{movementId}/items/{itemId}', [\App\Http\Controllers\Stock\StockMovementController::class, 'removeItem']);
});

// Include additional stock routes
require __DIR__.'/api/stock.php';