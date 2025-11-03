<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Stockage;

// Get statistics
$totalCount = Stockage::count();
$latestStockages = Stockage::latest()->limit(5)->get();

echo "=== STOCKAGE MANAGEMENT SEEDING VERIFICATION ===\n\n";
echo "Total Stockages in Database: {$totalCount}\n\n";

echo "Sample of Latest Seeded Stockages:\n";
echo str_repeat("-", 120) . "\n";

foreach ($latestStockages as $i => $stockage) {
    echo "\nStockage " . ($i + 1) . " (ID: {$stockage->id}):\n";
    echo "  Name: {$stockage->name}\n";
    echo "  Type: {$stockage->type}\n";
    echo "  Location: {$stockage->location}\n";
    echo "  Location Code: {$stockage->location_code}\n";
    echo "  Description: {$stockage->description}\n";
    echo "  Capacity: {$stockage->capacity} units\n";
    echo "  Status: {$stockage->status}\n";
    echo "  Temperature Controlled: " . ($stockage->temperature_controlled ? "Yes" : "No") . "\n";
    echo "  Security Level: {$stockage->security_level}\n";
    if ($stockage->warehouse_type) {
        echo "  Warehouse Type: {$stockage->warehouse_type}\n";
    }
    if ($stockage->service) {
        echo "  Service: {$stockage->service->name}\n";
    }
}

echo "\n" . str_repeat("-", 120) . "\n";
echo "\nData Distribution:\n";

// Count by type
$byType = Stockage::select('type')->selectRaw('COUNT(*) as count')->groupBy('type')->get();
echo "  By Type:\n";
foreach ($byType as $item) {
    echo "    {$item->type}: {$item->count}\n";
}

// Count by status
$byStatus = Stockage::select('status')->selectRaw('COUNT(*) as count')->groupBy('status')->get();
echo "\n  By Status:\n";
foreach ($byStatus as $item) {
    echo "    {$item->status}: {$item->count}\n";
}

// Count by security level
$bySecurity = Stockage::select('security_level')->selectRaw('COUNT(*) as count')->groupBy('security_level')->get();
echo "\n  By Security Level:\n";
foreach ($bySecurity as $item) {
    echo "    {$item->security_level}: {$item->count}\n";
}

// Temperature controlled count
$tempControlled = Stockage::where('temperature_controlled', true)->count();
$tempUncontrolled = Stockage::where('temperature_controlled', false)->count();
echo "\n  Temperature Control:\n";
echo "    Temperature Controlled: {$tempControlled}\n";
echo "    Standard Temperature: {$tempUncontrolled}\n";

// Average capacity
$avgCapacity = Stockage::avg('capacity');
$maxCapacity = Stockage::max('capacity');
$minCapacity = Stockage::min('capacity');
echo "\n  Capacity Statistics:\n";
echo "    Average Capacity: " . number_format($avgCapacity, 0) . " units\n";
echo "    Maximum Capacity: " . number_format($maxCapacity, 0) . " units\n";
echo "    Minimum Capacity: " . number_format($minCapacity, 0) . " units\n";

// Warehouse types
$warehouseTypes = Stockage::whereNotNull('warehouse_type')
    ->select('warehouse_type')
    ->selectRaw('COUNT(*) as count')
    ->groupBy('warehouse_type')
    ->get();
echo "\n  Warehouse Types:\n";
foreach ($warehouseTypes as $item) {
    echo "    {$item->warehouse_type}: {$item->count}\n";
}

// Services
$byService = Stockage::select('service_id')
    ->selectRaw('COUNT(*) as count')
    ->groupBy('service_id')
    ->with('service')
    ->get();
echo "\n  Distribution by Service:\n";
if ($byService->isNotEmpty()) {
    foreach ($byService as $item) {
        $serviceName = $item->service ? $item->service->name : 'Unknown';
        echo "    {$serviceName}: {$item->count}\n";
    }
}

echo "\n✓ Stockage seeding verification complete!\n";
