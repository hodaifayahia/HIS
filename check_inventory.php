<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

try {
    echo "Checking inventory records for products 14 and 10:\n";
    
    $inventories = Inventory::whereIn('product_id', [14, 10])->get();
    echo "Found {$inventories->count()} inventory records\n";
    
    foreach ($inventories as $inventory) {
        echo "Inventory ID: {$inventory->id}, Product ID: {$inventory->product_id}, Stockage ID: {$inventory->stockage_id}\n";
    }
    
    echo "\nTrying to delete inventory records manually:\n";
    $deletedCount = Inventory::whereIn('product_id', [14, 10])->delete();
    echo "Deleted {$deletedCount} inventory records\n";
    
    echo "\nNow trying to delete products:\n";
    $productDeleteCount = DB::table('products')->whereIn('id', [14, 10])->delete();
    echo "Deleted {$productDeleteCount} products\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}