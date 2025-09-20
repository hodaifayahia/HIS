<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

try {
    echo "=== Debugging CASCADE Delete Issue ===\n\n";
    
    // Check if foreign key checks are enabled
    $fkChecks = DB::select("SELECT @@foreign_key_checks as fk_checks");
    echo "Foreign key checks enabled: " . ($fkChecks[0]->fk_checks ? 'YES' : 'NO') . "\n\n";
    
    // Check products exist
    $products = Product::whereIn('id', [14, 10])->get();
    echo "Products found: {$products->count()}\n";
    foreach ($products as $product) {
        echo "  Product ID: {$product->id}, Name: {$product->name}\n";
    }
    echo "\n";
    
    // Check inventory records
    $inventories = Inventory::whereIn('product_id', [14, 10])->get();
    echo "Inventory records found: {$inventories->count()}\n";
    foreach ($inventories as $inventory) {
        echo "  Inventory ID: {$inventory->id}, Product ID: {$inventory->product_id}, Stockage ID: {$inventory->stockage_id}\n";
    }
    echo "\n";
    
    // Check if there are other tables referencing inventories
    echo "Checking tables that reference inventories:\n";
    $referencingTables = DB::select("
        SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_NAME = 'inventories' 
        AND CONSTRAINT_SCHEMA = DATABASE()
    ");
    
    if (count($referencingTables) > 0) {
        foreach ($referencingTables as $ref) {
            echo "  Table: {$ref->TABLE_NAME}, Column: {$ref->COLUMN_NAME}, Constraint: {$ref->CONSTRAINT_NAME}\n";
        }
    } else {
        echo "  No tables reference inventories\n";
    }
    echo "\n";
    
    // Try deleting with foreign key checks disabled
    echo "Attempting deletion with foreign key checks disabled:\n";
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    
    try {
        $deletedInventories = Inventory::whereIn('product_id', [14, 10])->delete();
        echo "Deleted {$deletedInventories} inventory records\n";
        
        $deletedProducts = Product::whereIn('id', [14, 10])->delete();
        echo "Deleted {$deletedProducts} products\n";
        
        echo "SUCCESS: Products deleted with FK checks disabled\n";
    } catch (Exception $e) {
        echo "ERROR even with FK checks disabled: " . $e->getMessage() . "\n";
    }
    
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}