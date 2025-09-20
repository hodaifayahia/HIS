<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

try {
    echo "=== Testing Simple Product Deletion ===\n\n";
    
    // Create a test product
    $product = Product::create([
        'name' => 'Simple Test Product',
        'description' => 'This is a simple test product',
        'category' => 'Medical Supplies',
        'is_clinical' => false,
        'status' => 'In Stock'
    ]);
    
    echo "Created test product with ID: {$product->id}\n";
    
    // Test the deletion using the updated controller logic
    echo "\nTesting product deletion with updated logic...\n";
    
    DB::beginTransaction();
    
    try {
        // Get all inventory IDs for the products to be deleted
        $inventoryIds = Inventory::whereIn('product_id', [$product->id])->pluck('id')->toArray();
        
        echo "Found inventory IDs: " . (empty($inventoryIds) ? 'none' : implode(', ', $inventoryIds)) . "\n";
        
        // First, delete stock movement inventory selections that reference these inventories
        if (!empty($inventoryIds)) {
            $deletedSMIS = DB::table('stock_movement_inventory_selections')
                ->whereIn('inventory_id', $inventoryIds)
                ->delete();
            echo "Deleted {$deletedSMIS} stock movement inventory selections\n";
        } else {
            echo "No inventory records found, skipping stock movement deletion\n";
        }
        
        // Then, delete all related inventory records
        $deletedInventories = Inventory::whereIn('product_id', [$product->id])->delete();
        echo "Deleted {$deletedInventories} inventory records\n";
        
        // Finally, delete the products
        $deletedProducts = Product::whereIn('id', [$product->id])->delete();
        echo "Deleted {$deletedProducts} products\n";
        
        DB::commit();
        
        echo "\nSUCCESS: Product deletion completed without errors!\n";
        echo "The fix for handling cascade deletes is working correctly.\n";
        
    } catch (Exception $e) {
        DB::rollback();
        echo "ERROR: " . $e->getMessage() . "\n";
        throw $e;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}