<?php

/**
 * Test script to verify the purchasing product flow
 * Tests both is_clinical=true (pharmacy) and is_clinical=false (stock)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PharmacyProduct;
use App\Models\Product;
use App\Services\Purchasing\PurchasingProductService;

echo "=== Testing Purchasing Product Flow ===\n\n";

$service = new PurchasingProductService;

// Test 1: Create a stock product (is_clinical = false)
echo "TEST 1: Creating Stock Product (is_clinical=false)\n";
echo "-------------------------------------------\n";
try {
    $stockData = [
        'name' => 'Test Stock Product '.time(),
        'description' => 'This should go to products table',
        'category' => 'Medical Supplies',  // Must match enum: 'Medical Supplies','Equipment','Medication','Others'
        'is_clinic' => false,  // Service layer uses is_clinic
    ];

    $stockProduct = $service->createProduct($stockData);

    if ($stockProduct instanceof Product) {
        echo "✓ Success! Product created in 'products' table\n";
        echo "  - ID: {$stockProduct->id}\n";
        echo "  - Name: {$stockProduct->name}\n";
        echo "  - Table: products\n";
        echo '  - is_clinical: '.($stockProduct->is_clinical ? 'true' : 'false')."\n";
    } else {
        echo "✗ Error: Expected Product model instance\n";
    }
} catch (\Exception $e) {
    echo '✗ Error: '.$e->getMessage()."\n";
}

echo "\n";

// Test 2: Create a clinical product (is_clinical = true)
echo "TEST 2: Creating Clinical Product (is_clinical=true)\n";
echo "-------------------------------------------\n";
try {
    $clinicalData = [
        'name' => 'Test Clinical Product '.time(),
        'description' => 'This should go to pharmacy_products table',
        'category' => 'Medication',
        'is_clinic' => true,  // Service layer uses is_clinic
        'unit_price' => 25.50,
    ];

    $clinicalProduct = $service->createProduct($clinicalData);

    if ($clinicalProduct instanceof PharmacyProduct) {
        echo "✓ Success! Product created in 'pharmacy_products' table\n";
        echo "  - ID: {$clinicalProduct->id}\n";
        echo "  - Name: {$clinicalProduct->name}\n";
        echo "  - Table: pharmacy_products\n";
        echo "  - Unit Cost: \${$clinicalProduct->unit_cost}\n";
    } else {
        echo "✗ Error: Expected PharmacyProduct model instance\n";
    }
} catch (\Exception $e) {
    echo '✗ Error: '.$e->getMessage()."\n";
}

echo "\n";

// Test 3: Retrieve combined products
echo "TEST 3: Retrieving Combined Products\n";
echo "-------------------------------------------\n";
try {
    $allProducts = $service->getProductsFromBothTables(['search' => 'Test']);

    echo '✓ Retrieved '.count($allProducts)." products from both tables\n";

    $stockCount = 0;
    $clinicalCount = 0;

    foreach ($allProducts as $product) {
        if (isset($product['is_clinical'])) {
            if ($product['is_clinical']) {
                $clinicalCount++;
            } else {
                $stockCount++;
            }
        }
    }

    echo "  - Stock products: $stockCount\n";
    echo "  - Clinical products: $clinicalCount\n";

    if (count($allProducts) > 0) {
        echo "\nSample Product Structure:\n";
        echo '  Fields: '.implode(', ', array_keys($allProducts[0]))."\n";

        // Check if is_clinical field exists
        if (isset($allProducts[0]['is_clinical'])) {
            echo "  ✓ 'is_clinical' field is present\n";
        } else {
            echo "  ✗ 'is_clinical' field is MISSING\n";
        }
    }

} catch (\Exception $e) {
    echo '✗ Error: '.$e->getMessage()."\n";
}

echo "\n";

// Test 4: Verify field mapping
echo "TEST 4: Verifying Field Mapping\n";
echo "-------------------------------------------\n";
try {
    $stockProducts = $service->getStockProducts(['search' => 'Test']);
    $pharmacyProducts = $service->getPharmacyProducts(['search' => 'Test']);

    echo "Stock Products:\n";
    if (! empty($stockProducts)) {
        $sample = $stockProducts[0];
        echo "  - has 'is_clinical' field: ".(isset($sample['is_clinical']) ? 'YES (value: '.($sample['is_clinical'] ? 'true' : 'false').')' : 'NO')."\n";
        echo "  - has 'is_clinic' field: ".(isset($sample['is_clinic']) ? 'YES' : 'NO')."\n";
        echo "  - has 'source' field: ".(isset($sample['source']) ? "YES (value: {$sample['source']})" : 'NO')."\n";
    } else {
        echo "  - No stock products found\n";
    }

    echo "\nPharmacy Products:\n";
    if (! empty($pharmacyProducts)) {
        $sample = $pharmacyProducts[0];
        echo "  - has 'is_clinical' field: ".(isset($sample['is_clinical']) ? 'YES (value: '.($sample['is_clinical'] ? 'true' : 'false').')' : 'NO')."\n";
        echo "  - has 'is_clinic' field: ".(isset($sample['is_clinic']) ? 'YES' : 'NO')."\n";
        echo "  - has 'source' field: ".(isset($sample['source']) ? "YES (value: {$sample['source']})" : 'NO')."\n";
    } else {
        echo "  - No pharmacy products found\n";
    }

} catch (\Exception $e) {
    echo '✗ Error: '.$e->getMessage()."\n";
}

echo "\n=== Test Complete ===\n";
