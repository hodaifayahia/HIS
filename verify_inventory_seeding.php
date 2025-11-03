<?php
// Verification script for inventory seeding
require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Inventory;

$totalInventories = Inventory::count();
echo "=== INVENTORY SEEDING VERIFICATION ===\n\n";
echo "Total Inventory Records: {$totalInventories}\n\n";

// Expiry status breakdown
$validStock = Inventory::where('expiry_date', '>', now())->count();
$expiringSoon = Inventory::whereBetween('expiry_date', [now()->subDays(60), now()])->count();
$expired = Inventory::where('expiry_date', '<', now())->count();

echo "EXPIRY STATUS:\n";
echo "  Valid Stock (future expiry): {$validStock} (" . round(($validStock/$totalInventories)*100, 1) . "%)\n";
echo "  Expiring Soon (< 60 days): {$expiringSoon} (" . round(($expiringSoon/$totalInventories)*100, 1) . "%)\n";
echo "  Expired: {$expired} (" . round(($expired/$totalInventories)*100, 1) . "%)\n\n";

// Unit distribution
$unitCounts = Inventory::select('unit')
    ->selectRaw('COUNT(*) as count')
    ->groupBy('unit')
    ->orderByRaw('COUNT(*) DESC')
    ->get();

echo "UNIT DISTRIBUTION:\n";
foreach ($unitCounts as $uc) {
    echo "  {$uc->unit}: {$uc->count}\n";
}
echo "\n";

// Quantity statistics
$quantityStats = Inventory::selectRaw('MIN(quantity) as min, MAX(quantity) as max, AVG(quantity) as avg, SUM(total_units) as total_units')
    ->first();

echo "QUANTITY STATISTICS:\n";
echo "  Min Quantity: {$quantityStats->min}\n";
echo "  Max Quantity: {$quantityStats->max}\n";
echo "  Avg Quantity: " . round($quantityStats->avg, 2) . "\n";
echo "  Total Units in Stock: {$quantityStats->total_units}\n\n";

// Price statistics
$priceStats = Inventory::selectRaw('MIN(purchase_price) as min, MAX(purchase_price) as max, AVG(purchase_price) as avg')
    ->first();

echo "PURCHASE PRICE STATISTICS:\n";
echo "  Min Price: $" . round($priceStats->min, 2) . "\n";
echo "  Max Price: $" . round($priceStats->max, 2) . "\n";
echo "  Avg Price: $" . round($priceStats->avg, 2) . "\n\n";

// Product coverage
$productsInInventory = Inventory::distinct('product_id')->count();
$totalProducts = \App\Models\Product::count();

echo "PRODUCT COVERAGE:\n";
echo "  Products in Inventory: {$productsInInventory}\n";
echo "  Total Products: {$totalProducts}\n";
echo "  Coverage: " . round(($productsInInventory/$totalProducts)*100, 1) . "%\n\n";

// Stockage coverage
$stockagesInInventory = Inventory::distinct('stockage_id')->count();
$totalStockages = \App\Models\Stockage::count();

echo "STOCKAGE COVERAGE:\n";
echo "  Stockages with Inventory: {$stockagesInInventory}\n";
echo "  Total Stockages: {$totalStockages}\n";
echo "  Coverage: " . round(($stockagesInInventory/$totalStockages)*100, 1) . "%\n\n";

// Serial number stats
$withSerial = Inventory::whereNotNull('serial_number')->count();
$withoutSerial = Inventory::whereNull('serial_number')->count();

echo "SERIAL NUMBER DISTRIBUTION:\n";
echo "  With Serial Number: {$withSerial} (" . round(($withSerial/$totalInventories)*100, 1) . "%)\n";
echo "  Without Serial Number: {$withoutSerial} (" . round(($withoutSerial/$totalInventories)*100, 1) . "%)\n\n";

// Batch number sample
$batchSamples = Inventory::select('batch_number')
    ->distinct()
    ->limit(5)
    ->get();

echo "SAMPLE BATCH NUMBERS:\n";
foreach ($batchSamples as $batch) {
    echo "  {$batch->batch_number}\n";
}
echo "\n";

// Location distribution
$locations = Inventory::select('location')
    ->selectRaw('COUNT(*) as count')
    ->groupBy('location')
    ->orderByRaw('COUNT(*) DESC')
    ->limit(10)
    ->get();

echo "TOP 10 STORAGE LOCATIONS:\n";
foreach ($locations as $loc) {
    echo "  {$loc->location}: {$loc->count}\n";
}
echo "\n";

echo "✓ Inventory seeding verification complete!\n";
