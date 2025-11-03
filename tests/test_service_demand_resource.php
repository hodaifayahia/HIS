<?php

/**
 * Test script to verify ServiceDemandItemResource works correctly
 * Run with: php artisan tinker < tests/test_service_demand_resource.php
 */

use App\Http\Resources\ServiceDemandItemResource;
use App\Models\ServiceDemendPurchcingItem;

echo "=== Testing ServiceDemandItemResource ===\n\n";

// Test 1: Get an existing item and transform it
$item = ServiceDemendPurchcingItem::with('product', 'pharmacyProduct')->first();

if ($item) {
    echo "Item found (ID: {$item->id})\n";
    echo "Product ID: " . ($item->product_id ?? 'NULL') . "\n";
    echo "Pharmacy Product ID: " . ($item->pharmacy_product_id ?? 'NULL') . "\n\n";

    // Transform with resource
    $resource = new ServiceDemandItemResource($item);
    $transformed = $resource->resolve();

    echo "Transformed Data:\n";
    echo json_encode($transformed, JSON_PRETTY_PRINT) . "\n\n";

    // Verify fields
    echo "Verification:\n";
    echo "✓ Has product_source: " . (isset($transformed['product_source']) ? 'YES' : 'NO') . "\n";
    echo "✓ Product source value: " . ($transformed['product_source'] ?? 'NULL') . "\n";
    echo "✓ Has product object: " . (isset($transformed['product']) ? 'YES' : 'NO') . "\n";
    echo "✓ Product name: " . ($transformed['product']['name'] ?? 'NULL') . "\n";
} else {
    echo "No items found in database. Please create a service demand item first.\n";
}

echo "\n=== Test Complete ===\n";
