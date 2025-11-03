<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

// Get statistics
$totalCount = Product::count();
$latestProducts = Product::latest()->limit(3)->get();

echo "=== PRODUCT TABLE SEEDING VERIFICATION ===\n\n";
echo "Total Products in Database: {$totalCount}\n\n";

echo "Sample of Latest Seeded Products:\n";
echo str_repeat("-", 100) . "\n";

foreach ($latestProducts as $i => $product) {
    echo "\nProduct " . ($i + 1) . " (ID: {$product->id}):\n";
    echo "  Name: {$product->name}\n";
    echo "  Description: {$product->description}\n";
    echo "  Category: {$product->category}\n";
    echo "  Is Clinical: " . ($product->is_clinical ? "Yes" : "No") . "\n";
    echo "  Code Interne: {$product->code_interne}\n";
    echo "  Code PCH: {$product->code_pch}\n";
    echo "  Designation: {$product->designation}\n";
    echo "  Type Medicament: {$product->type_medicament}\n";
    echo "  Forme: {$product->forme}\n";
    echo "  Code: {$product->code}\n";
    echo "  Boite De: {$product->boite_de}\n";
    echo "  Quantity By Box: {$product->quantity_by_box}\n";
    echo "  Nom Commercial: {$product->nom_commercial}\n";
    echo "  Status: {$product->status}\n";
    echo "  Request Approval: " . ($product->is_request_approval ? "Yes" : "No") . "\n";
}

echo "\n" . str_repeat("-", 100) . "\n";
echo "\nData Distribution:\n";
echo "  Categories: " . Product::distinct('category')->count('category') . " distinct\n";
echo "  Clinical Products: " . Product::where('is_clinical', true)->count() . "\n";
echo "  Non-Clinical: " . Product::where('is_clinical', false)->count() . "\n";
echo "  Require Approval: " . Product::where('is_request_approval', true)->count() . "\n";
echo "  In Stock: " . Product::where('status', 'In Stock')->count() . "\n";
echo "  Low Stock: " . Product::where('status', 'Low Stock')->count() . "\n";
echo "  Out of Stock: " . Product::where('status', 'Out of Stock')->count() . "\n";
echo "  Average Quantity per Box: " . number_format(Product::avg('quantity_by_box'), 1) . "\n";

echo "\n✓ Product seeding verification complete!\n";
