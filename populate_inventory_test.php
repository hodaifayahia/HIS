<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CONFIGURATION\Service;
use App\Models\Stockage;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

try {
    echo "Starting database population for Service Stock testing...\n";

    // Create sample services
    $services = [
        ['name' => 'Pharmacy Service', 'service_abv' => 'PHAR', 'description' => 'Main pharmacy service', 'image_url' => '/images/services/pharmacy.png', 'start_date' => now(), 'end_date' => null, 'agmentation' => 0, 'is_active' => true],
        ['name' => 'Emergency Service', 'service_abv' => 'EMER', 'description' => 'Emergency department', 'image_url' => '/images/services/emergency.png', 'start_date' => now(), 'end_date' => null, 'agmentation' => 0, 'is_active' => true],
        ['name' => 'Surgery Service', 'service_abv' => 'SURG', 'description' => 'Surgical department', 'image_url' => '/images/services/surgery.png', 'start_date' => now(), 'end_date' => null, 'agmentation' => 0, 'is_active' => true],
    ];

    foreach ($services as $serviceData) {
        $service = Service::firstOrCreate(
            ['name' => $serviceData['name']],
            $serviceData
        );
        echo "Created service: {$service->name} (ID: {$service->id})\n";
    }

    // Create sample stockages for each service
    $stockages = [
        ['name' => 'Main Pharmacy Warehouse', 'service_id' => 1, 'type' => 'pharmacy', 'location' => 'Building A'],
        ['name' => 'Emergency Storage', 'service_id' => 2, 'type' => 'emergency', 'location' => 'Emergency Wing'],
        ['name' => 'Surgical Supplies', 'service_id' => 3, 'type' => 'warehouse', 'location' => 'Surgery Wing'],
    ];

    foreach ($stockages as $stockageData) {
        $stockage = Stockage::firstOrCreate(
            ['name' => $stockageData['name']],
            $stockageData
        );
        echo "Created stockage: {$stockage->name} (ID: {$stockage->id})\n";
    }

    // Get existing products
    $products = Product::all();
    if ($products->isEmpty()) {
        echo "No products found. Please run ProductSeeder first.\n";
        exit(1);
    }

    $productIds = $products->pluck('id')->toArray();
    echo "Available product IDs: " . implode(', ', $productIds) . "\n";

    // Create sample inventory items using actual product IDs
    $inventoryItems = [
        ['product_id' => $productIds[0] ?? 1, 'stockage_id' => 4, 'quantity' => 100, 'unit' => 'pieces'],
        ['product_id' => $productIds[1] ?? 2, 'stockage_id' => 4, 'quantity' => 50, 'unit' => 'pieces'],
        ['product_id' => $productIds[2] ?? 3, 'stockage_id' => 4, 'quantity' => 200, 'unit' => 'tablets'],
        ['product_id' => $productIds[3] ?? 4, 'stockage_id' => 5, 'quantity' => 25, 'unit' => 'pieces'],
        ['product_id' => $productIds[4] ?? 5, 'stockage_id' => 5, 'quantity' => 100, 'unit' => 'pieces'],
        ['product_id' => $productIds[5] ?? 6, 'stockage_id' => 6, 'quantity' => 150, 'unit' => 'capsules'],
    ];

    foreach ($inventoryItems as $item) {
        $inventory = Inventory::firstOrCreate(
            ['product_id' => $item['product_id'], 'stockage_id' => $item['stockage_id']],
            $item
        );
        echo "Created inventory item: Product {$item['product_id']} in Stockage {$item['stockage_id']} (Qty: {$item['quantity']})\n";
    }

    echo "\n✅ Database population completed successfully!\n";
    echo "You can now test the Service Stock Management page.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
