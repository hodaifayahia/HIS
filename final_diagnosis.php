<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

try {
    echo "=== Final Diagnosis of CASCADE Delete Issue ===\n\n";
    
    // Create a test product
    $product = Product::create([
        'name' => 'Diagnosis Test Product',
        'description' => 'This is a diagnosis test product',
        'category' => 'Medical Supplies',
        'is_clinical' => false,
        'status' => 'In Stock'
    ]);
    
    echo "Created test product with ID: {$product->id}\n";
    
    // Check if any inventory records were automatically created
    $inventories = Inventory::where('product_id', $product->id)->get();
    echo "Inventory records found after product creation: {$inventories->count()}\n";
    
    foreach ($inventories as $inv) {
        echo "  - Inventory ID: {$inv->id}, Quantity: {$inv->quantity}, Stockage: {$inv->stockage_id}\n";
    }
    
    // Check the actual foreign key constraint in the database
    echo "\nChecking foreign key constraint details:\n";
    $constraints = DB::select("
        SELECT 
            CONSTRAINT_NAME,
            DELETE_RULE,
            UPDATE_RULE
        FROM information_schema.REFERENTIAL_CONSTRAINTS 
        WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND TABLE_NAME = 'inventories'
        AND REFERENCED_TABLE_NAME = 'products'
    ");
    
    foreach ($constraints as $constraint) {
        echo "  Constraint: {$constraint->CONSTRAINT_NAME}\n";
        echo "  Delete Rule: {$constraint->DELETE_RULE}\n";
        echo "  Update Rule: {$constraint->UPDATE_RULE}\n";
    }
    
    // Try different deletion approaches
    echo "\n=== Testing Different Deletion Approaches ===\n";
    
    // Approach 1: Direct product deletion (should work with CASCADE)
    echo "\nApproach 1: Direct product deletion with CASCADE\n";
    try {
        $testProduct1 = Product::create([
            'name' => 'Test Direct Delete',
            'category' => 'Medical Supplies',
            'status' => 'In Stock'
        ]);
        echo "Created product {$testProduct1->id}\n";
        
        $testProduct1->delete();
        echo "SUCCESS: Direct deletion worked\n";
    } catch (Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
    }
    
    // Approach 2: Manual cascade deletion
    echo "\nApproach 2: Manual cascade deletion\n";
    try {
        $testProduct2 = Product::create([
            'name' => 'Test Manual Delete',
            'category' => 'Medical Supplies', 
            'status' => 'In Stock'
        ]);
        echo "Created product {$testProduct2->id}\n";
        
        DB::beginTransaction();
        
        // Check for inventories
        $inventoryIds = Inventory::where('product_id', $testProduct2->id)->pluck('id')->toArray();
        echo "Found inventory IDs: " . (empty($inventoryIds) ? 'none' : implode(', ', $inventoryIds)) . "\n";
        
        // Delete stock movement selections
        if (!empty($inventoryIds)) {
            $deletedSMIS = DB::table('stock_movement_inventory_selections')
                ->whereIn('inventory_id', $inventoryIds)
                ->delete();
            echo "Deleted {$deletedSMIS} stock movement selections\n";
        }
        
        // Delete inventories
        $deletedInv = Inventory::where('product_id', $testProduct2->id)->delete();
        echo "Deleted {$deletedInv} inventories\n";
        
        // Delete product
        $testProduct2->delete();
        echo "SUCCESS: Manual cascade deletion worked\n";
        
        DB::commit();
    } catch (Exception $e) {
        DB::rollback();
        echo "FAILED: " . $e->getMessage() . "\n";
    }
    
    // Clean up the original test product
    echo "\nCleaning up original test product...\n";
    try {
        DB::beginTransaction();
        
        $inventoryIds = Inventory::where('product_id', $product->id)->pluck('id')->toArray();
        if (!empty($inventoryIds)) {
            DB::table('stock_movement_inventory_selections')
                ->whereIn('inventory_id', $inventoryIds)
                ->delete();
        }
        Inventory::where('product_id', $product->id)->delete();
        $product->delete();
        
        DB::commit();
        echo "Cleanup successful\n";
    } catch (Exception $e) {
        DB::rollback();
        echo "Cleanup failed: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}