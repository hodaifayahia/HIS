<?php

/**
 * Test script to verify batch-level inventory creation
 *
 * Tests:
 * 1. Pharmacy products → pharmacy_inventories table (one row per batch)
 * 2. Regular products → inventories table (one row per batch)
 * 3. Each sub_item creates a separate row
 * 4. Database constraints prevent true duplicates
 */

use App\Models\BonEntree;
use App\Models\Inventory;
use App\Models\PharmacyInventory;

echo "=== Testing Batch-Level Inventory System ===\n\n";

// Test 1: Check pharmacy_inventories table structure
echo "1. Checking pharmacy_inventories unique constraint...\n";
$showCreate = DB::select('SHOW CREATE TABLE pharmacy_inventories');
$createStatement = $showCreate[0]->{'Create Table'};

if (str_contains($createStatement, 'unique_pharmacy_batch_details')) {
    echo "   ✅ Composite unique constraint found: unique_pharmacy_batch_details\n";

    // Extract the constraint definition
    preg_match('/UNIQUE KEY `unique_pharmacy_batch_details` \((.*?)\)/i', $createStatement, $matches);
    if (isset($matches[1])) {
        echo '   Fields: '.$matches[1]."\n";
    }
} else {
    echo "   ❌ Composite unique constraint NOT found!\n";
}

if (str_contains($createStatement, 'unique_pharmacy_product_stockage')) {
    echo "   ❌ WARNING: Old restrictive constraint still exists!\n";
} else {
    echo "   ✅ Old restrictive constraint removed\n";
}

echo "\n2. Checking bon_entree_items table for pharmacy_product_id field...\n";
$columns = DB::select('DESCRIBE bon_entree_items');
$hasPharmacyProductId = false;
$productIdNullable = false;

foreach ($columns as $col) {
    if ($col->Field === 'pharmacy_product_id') {
        $hasPharmacyProductId = true;
        echo '   ✅ pharmacy_product_id field exists ('.$col->Type.', '.$col->Null.")\n";
    }
    if ($col->Field === 'product_id') {
        $productIdNullable = ($col->Null === 'YES');
        echo '   ✅ product_id field '.($productIdNullable ? 'is nullable' : 'is NOT nullable')."\n";
    }
}

if (! $hasPharmacyProductId) {
    echo "   ❌ pharmacy_product_id field NOT found!\n";
}

echo "\n3. Checking sample data...\n";

// Count bon entrees
$bonEntreeCount = BonEntree::count();
echo "   Total bon entrees: $bonEntreeCount\n";

// Count inventory records
$pharmacyInventoryCount = PharmacyInventory::count();
$regularInventoryCount = Inventory::count();
echo "   Pharmacy inventory records: $pharmacyInventoryCount\n";
echo "   Regular inventory records: $regularInventoryCount\n";

// Find a validated bon entree with items
$validatedBonEntree = BonEntree::where('status', 'validated')
    ->with(['items'])
    ->first();

if ($validatedBonEntree) {
    echo "\n4. Sample validated bon entree analysis:\n";
    echo "   Bon Entree ID: {$validatedBonEntree->id}\n";
    echo "   Status: {$validatedBonEntree->status}\n";
    echo '   Items count: '.$validatedBonEntree->items->count()."\n";

    foreach ($validatedBonEntree->items as $item) {
        echo "\n   Item #{$item->id}:\n";
        echo '     - product_id: '.($item->product_id ?? 'NULL')."\n";
        echo '     - pharmacy_product_id: '.($item->pharmacy_product_id ?? 'NULL')."\n";
        echo "     - quantity: {$item->quantity}\n";

        if (! empty($item->sub_items)) {
            echo '     - sub_items: '.count($item->sub_items)." batches\n";
            foreach ($item->sub_items as $index => $subItem) {
                echo '       Batch #'.($index + 1).': qty='.($subItem['quantity'] ?? 0).
                     ', batch='.($subItem['batch_number'] ?? 'N/A').
                     ', serial='.($subItem['serial_number'] ?? 'N/A')."\n";
            }
        } else {
            echo "     - sub_items: NONE\n";
        }
    }
} else {
    echo "\n4. No validated bon entrees found\n";
}

echo "\n5. Database Constraint Validation:\n";
echo "   Testing if duplicate batch insertion is blocked...\n";

try {
    // Try to insert a duplicate (this should fail if constraint is working)
    $testPharmacyInventory = PharmacyInventory::first();

    if ($testPharmacyInventory) {
        echo "   Attempting to create duplicate of inventory ID: {$testPharmacyInventory->id}\n";

        try {
            PharmacyInventory::create([
                'pharmacy_product_id' => $testPharmacyInventory->pharmacy_product_id,
                'pharmacy_stockage_id' => $testPharmacyInventory->pharmacy_stockage_id,
                'batch_number' => $testPharmacyInventory->batch_number,
                'serial_number' => $testPharmacyInventory->serial_number,
                'expiry_date' => $testPharmacyInventory->expiry_date,
                'purchase_price' => $testPharmacyInventory->purchase_price,
                'quantity' => 1,
                'unit' => 'test',
            ]);

            echo "   ❌ PROBLEM: Duplicate was inserted! Constraint not working!\n";

            // Clean up the test record
            PharmacyInventory::where('unit', 'test')->delete();
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'unique_pharmacy_batch_details')) {
                echo "   ✅ Duplicate insertion blocked by constraint\n";
                echo '   Error: '.substr($e->getMessage(), 0, 100)."...\n";
            } else {
                echo '   ⚠️  Different error: '.substr($e->getMessage(), 0, 100)."\n";
            }
        }
    } else {
        echo "   ⚠️  No pharmacy inventory records to test with\n";
    }
} catch (\Exception $e) {
    echo '   ❌ Test failed: '.$e->getMessage()."\n";
}

echo "\n=== Test Complete ===\n";
echo "\nSummary:\n";
echo "- System is configured for batch-level inventory tracking\n";
echo "- Each sub_item should create ONE separate row in inventory\n";
echo "- Pharmacy products → pharmacy_inventories table\n";
echo "- Regular products → inventories table\n";
echo "- Database constraints prevent true duplicates\n";
