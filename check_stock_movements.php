<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "=== Checking Stock Movement Inventory Selections ===\n\n";
    
    // First, let's see if there are any inventory records that were related to products 14 and 10
    echo "Checking for any inventory records that might have existed for products 14 and 10:\n";
    
    // Check stock_movement_inventory_selections for any records that might reference inventories
    // that belonged to products 14 and 10
    $stockMovements = DB::select("
        SELECT smis.*, i.product_id 
        FROM stock_movement_inventory_selections smis
        LEFT JOIN inventories i ON smis.inventory_id = i.id
        WHERE i.product_id IN (14, 10) OR i.id IS NULL
    ");
    
    echo "Found " . count($stockMovements) . " stock movement inventory selections\n";
    
    foreach ($stockMovements as $sm) {
        echo "  SMIS ID: {$sm->id}, Inventory ID: {$sm->inventory_id}, Product ID: " . ($sm->product_id ?? 'NULL') . "\n";
    }
    
    // Check for orphaned records in stock_movement_inventory_selections
    echo "\nChecking for orphaned stock movement inventory selections:\n";
    $orphanedRecords = DB::select("
        SELECT smis.* 
        FROM stock_movement_inventory_selections smis
        LEFT JOIN inventories i ON smis.inventory_id = i.id
        WHERE i.id IS NULL
    ");
    
    echo "Found " . count($orphanedRecords) . " orphaned records\n";
    
    foreach ($orphanedRecords as $orphan) {
        echo "  Orphaned SMIS ID: {$orphan->id}, References non-existent Inventory ID: {$orphan->inventory_id}\n";
    }
    
    // Let's also check if there are any inventory records that reference products 14 and 10
    // but might have been soft deleted or in a different state
    echo "\nChecking all inventory records (including soft deleted):\n";
    $allInventories = DB::select("SELECT * FROM inventories WHERE product_id IN (14, 10)");
    echo "Found " . count($allInventories) . " inventory records (including soft deleted)\n";
    
    foreach ($allInventories as $inv) {
        echo "  Inventory ID: {$inv->id}, Product ID: {$inv->product_id}, Deleted At: " . ($inv->deleted_at ?? 'NULL') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}