<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

try {
    echo "=== Testing Product Deletion Fix ===\n\n";
    
    // Create a test product
    $product = Product::create([
        'name' => 'Test Product for Deletion',
        'description' => 'This is a test product',
        'category' => 'Medical Supplies',
        'is_clinical' => false,
        'status' => 'In Stock'
    ]);
    
    echo "Created test product with ID: {$product->id}\n";
    
    // Create a test inventory record
    $inventory = Inventory::create([
        'product_id' => $product->id,
        'stockage_id' => 1, // Assuming stockage ID 1 exists
        'quantity' => 10,
        'unit' => 'pieces',
        'batch_number' => 'TEST001',
        'expiry_date' => now()->addMonths(6)
    ]);
    
    echo "Created test inventory with ID: {$inventory->id}\n";
    
    // Create a test stock movement inventory selection
    DB::table('stock_movement_inventory_selections')->insert([
        'inventory_id' => $inventory->id,
        'stock_movement_id' => 1, // Assuming stock movement ID 1 exists
        'quantity_moved' => 5,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo "Created test stock movement inventory selection\n";
    
    // Now test the deletion
    echo "\nTesting product deletion...\n";
    
    // Simulate the controller's bulkDelete method
    DB::beginTransaction();
    
    try {
        // Get all inventory IDs for the products to be deleted
        $inventoryIds = Inventory::whereIn('product_id', [$product->id])->pluck('id')->toArray();
        
        echo "Found inventory IDs: " . implode(', ', $inventoryIds) . "\n";
        
        // First, delete stock movement inventory selections that reference these inventories
        if (!empty($inventoryIds)) {
            $deletedSMIS = DB::table('stock_movement_inventory_selections')
                ->whereIn('inventory_id', $inventoryIds)
                ->delete();
            echo "Deleted {$deletedSMIS} stock movement inventory selections\n";
        }
        
        // Then, delete all related inventory records
        $deletedInventories = Inventory::whereIn('product_id', [$product->id])->delete();
        echo "Deleted {$deletedInventories} inventory records\n";
        
        // Finally, delete the products
        $deletedProducts = Product::whereIn('id', [$product->id])->delete();
        echo "Deleted {$deletedProducts} products\n";
        
        DB::commit();
        
        echo "\nSUCCESS: Product deletion completed without errors!\n";
        
    } catch (Exception $e) {
        DB::rollback();
        echo "ERROR: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}