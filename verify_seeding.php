<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PharmacyProduct;

// Get statistics
$totalCount = PharmacyProduct::count();
$latestProducts = PharmacyProduct::latest()->limit(3)->get();

echo "=== PHARMACY PRODUCT SEEDING VERIFICATION ===\n\n";
echo "Total Products in Database: {$totalCount}\n\n";

echo "Sample of Latest Seeded Products:\n";
echo str_repeat("-", 80) . "\n";

foreach ($latestProducts as $i => $product) {
    echo "\nProduct " . ($i + 1) . " (ID: {$product->id}):\n";
    echo "  Name: {$product->name}\n";
    echo "  Generic Name: {$product->generic_name}\n";
    echo "  Brand Name: {$product->brand_name}\n";
    echo "  Manufacturer: {$product->manufacturer}\n";
    echo "  Supplier: {$product->supplier}\n";
    echo "  Category: {$product->category}\n";
    echo "  Dosage Form: {$product->dosage_form}\n";
    echo "  Route: {$product->route_of_administration}\n";
    echo "  Strength: {$product->strength} {$product->strength_unit}\n";
    echo "  Unit of Measure: {$product->unit_of_measure}\n";
    echo "  Unit Cost: \${$product->unit_cost}\n";
    echo "  Selling Price: \${$product->selling_price}\n";
    echo "  Expiry Date: {$product->expiry_date}\n";
    echo "  Storage Conditions: {$product->storage_conditions}\n";
    echo "  Active: " . ($product->is_active ? "Yes" : "No") . "\n";
    echo "  Controlled Substance: " . ($product->is_controlled_substance ? "Yes" : "No") . "\n";
}

echo "\n" . str_repeat("-", 80) . "\n";
echo "\nData Distribution:\n";
echo "  Distinct Categories: " . PharmacyProduct::distinct('category')->count('category') . "\n";
echo "  Distinct Manufacturers: " . PharmacyProduct::distinct('manufacturer')->count('manufacturer') . "\n";
echo "  Distinct Suppliers: " . PharmacyProduct::distinct('supplier')->count('supplier') . "\n";
echo "  Average Unit Cost: $" . number_format(PharmacyProduct::avg('unit_cost'), 2) . "\n";
echo "  Average Selling Price: $" . number_format(PharmacyProduct::avg('selling_price'), 2) . "\n";
echo "  Average Markup: " . number_format(PharmacyProduct::avg('markup_percentage'), 1) . "%\n";
echo "  Active Products: " . PharmacyProduct::where('is_active', true)->count() . "\n";
echo "  Controlled Substances: " . PharmacyProduct::where('is_controlled_substance', true)->count() . "\n";

echo "\n✓ Seeding verification complete!\n";
