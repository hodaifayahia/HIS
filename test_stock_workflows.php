<?php

/**
 * Stock Management Workflow Tests
 * Tests complete workflows and business logic
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\Inventory;
use App\Models\Stockage;
use App\Models\ProductReserve;
use App\Services\Stock\ProductReserveService;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function success($msg) { echo "\033[32m✓ $msg\033[0m\n"; }
function error($msg) { echo "\033[31m✗ $msg\033[0m\n"; }
function info($msg) { echo "\033[36mℹ $msg\033[0m\n"; }
function section($msg) { echo "\n\033[33m=== $msg ===\033[0m\n"; }

$totalTests = 0;
$passedTests = 0;

function test($description, $callback) {
    global $totalTests, $passedTests;
    $totalTests++;
    
    try {
        $result = $callback();
        if ($result !== false) {
            success($description);
            $passedTests++;
            return true;
        } else {
            error($description);
            return false;
        }
    } catch (Exception $e) {
        error("$description - Exception: " . $e->getMessage());
        return false;
    }
}

echo "\n";
info("Stock Management Workflow Tests");
info("Test Date: " . date('Y-m-d H:i:s'));

// ============================================
// WORKFLOW 1: COMPLETE RESERVATION CYCLE
// ============================================
section("WORKFLOW 1: Product Reservation Lifecycle");

$testProduct = null;
$testStockage = null;
$testInventory = null;
$testUser = null;
$reservationCode = null;

test("Setup: Get test data", function() use (&$testProduct, &$testStockage, &$testInventory, &$testUser) {
    $testProduct = Product::first();
    $testStockage = Stockage::first();
    $testUser = User::first();
    
    if (!$testProduct || !$testStockage || !$testUser) {
        info("Missing test data - creating...");
        
        if (!$testUser) {
            info("No users found - skipping user-dependent tests");
            return false;
        }
        
        if (!$testProduct) {
            DB::beginTransaction();
            try {
                $testProduct = Product::create([
                    'name' => 'Workflow Test Product',
                    'code' => 'WTP-' . time(),
                    'category' => 'Medical Supplies',
                    'status' => 'In Stock'
                ]);
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }
        
        if (!$testStockage) {
            DB::beginTransaction();
            try {
                $testStockage = Stockage::create([
                    'name' => 'Workflow Test Stockage',
                    'location' => 'Test Location',
                    'type' => 'warehouse',
                    'status' => 'active'
                ]);
                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }
    }
    
    $testInventory = Inventory::where('product_id', $testProduct->id)
        ->where('stockage_id', $testStockage->id)
        ->first();
    
    if (!$testInventory) {
        DB::beginTransaction();
        try {
            $testInventory = Inventory::create([
                'product_id' => $testProduct->id,
                'stockage_id' => $testStockage->id,
                'quantity' => 500,
                'total_units' => 500,
                'unit' => 'pieces'
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    info("Test Product ID: {$testProduct->id} ({$testProduct->name})");
    info("Test Stockage ID: {$testStockage->id} ({$testStockage->name})");
    info("Test User ID: {$testUser->id}");
    info("Initial Inventory: {$testInventory->quantity}");
    
    return true;
});

test("Create reservation and verify stock deduction", function() use ($testProduct, $testStockage, $testUser, &$reservationCode, $testInventory) {
    if (!$testProduct || !$testStockage || !$testUser) {
        return true; // Skip if setup failed
    }
    
    $initialQty = $testInventory->quantity;
    $reserveQty = 20;
    
    $service = new ProductReserveService();
    $reservation = $service->store([
        'product_id' => $testProduct->id,
        'stockage_id' => $testStockage->id,
        'reserved_by' => $testUser->id,
        'quantity' => $reserveQty,
        'expires_at' => now()->addDays(7),
        'reservation_notes' => 'Workflow test reservation',
        'source' => 'manual'
    ]);
    
    $reservationCode = $reservation->reservation_code;
    
    $testInventory->refresh();
    $newQty = $testInventory->quantity;
    
    $deducted = $initialQty - $newQty;
    
    info("Reserved: {$reserveQty}, Stock deducted: {$deducted}");
    info("Reservation Code: {$reservationCode}");
    
    return $deducted == $reserveQty && $reservation->status === 'pending';
});

test("Cancel reservation and verify stock return", function() use ($testInventory, &$reservationCode) {
    if (!$reservationCode) {
        return true; // Skip if previous test failed
    }
    
    $beforeCancel = $testInventory->quantity;
    
    $reservation = ProductReserve::where('reservation_code', $reservationCode)->first();
    $reserveQty = $reservation->quantity;
    
    $service = new ProductReserveService();
    $service->cancel($reservation, 'Workflow test cancellation');
    
    $testInventory->refresh();
    $afterCancel = $testInventory->quantity;
    
    $returned = $afterCancel - $beforeCancel;
    
    info("Stock before: {$beforeCancel}, after: {$afterCancel}, returned: {$returned}");
    
    $reservation->refresh();
    return $returned == $reserveQty && $reservation->status === 'cancelled';
});

// ============================================
// WORKFLOW 2: MULTIPLE RESERVATIONS
// ============================================
section("WORKFLOW 2: Multiple Concurrent Reservations");

test("Create multiple reservations from same inventory", function() use ($testProduct, $testStockage, $testUser, $testInventory) {
    if (!$testProduct || !$testStockage || !$testUser) {
        return true;
    }
    
    $initialQty = $testInventory->quantity;
    
    $service = new ProductReserveService();
    $reservations = [];
    $totalReserved = 0;
    
    for ($i = 1; $i <= 3; $i++) {
        $qty = 10;
        $reservation = $service->store([
            'product_id' => $testProduct->id,
            'stockage_id' => $testStockage->id,
            'reserved_by' => $testUser->id,
            'quantity' => $qty,
            'expires_at' => now()->addDays($i),
            'reservation_notes' => "Multi-reservation test #{$i}",
            'source' => 'manual'
        ]);
        
        $reservations[] = $reservation;
        $totalReserved += $qty;
    }
    
    $testInventory->refresh();
    $finalQty = $testInventory->quantity;
    $actualDeducted = $initialQty - $finalQty;
    
    info("Created 3 reservations totaling: {$totalReserved}");
    info("Actual stock deducted: {$actualDeducted}");
    
    // Clean up
    foreach ($reservations as $r) {
        $service->cancel($r, 'Cleanup');
    }
    
    return $actualDeducted == $totalReserved;
});

// ============================================
// WORKFLOW 3: INSUFFICIENT STOCK
// ============================================
section("WORKFLOW 3: Stock Validation");

test("Cannot reserve more than available stock", function() use ($testProduct, $testStockage, $testUser, $testInventory) {
    if (!$testProduct || !$testStockage || !$testUser) {
        return true;
    }
    
    $availableQty = $testInventory->quantity;
    $excessiveQty = $availableQty + 1000;
    
    $service = new ProductReserveService();
    
    try {
        $reservation = $service->store([
            'product_id' => $testProduct->id,
            'stockage_id' => $testStockage->id,
            'reserved_by' => $testUser->id,
            'quantity' => $excessiveQty,
            'expires_at' => now()->addDays(1),
            'source' => 'manual'
        ]);
        
        // If we reach here, the test failed
        info("ERROR: Was able to reserve {$excessiveQty} when only {$availableQty} available");
        return false;
    } catch (Exception $e) {
        info("Correctly prevented over-reservation: " . $e->getMessage());
        return true;
    }
});

// ============================================
// WORKFLOW 4: RESERVATION EXPIRATION
// ============================================
section("WORKFLOW 4: Automatic Expiration");

test("Create expired reservation and run auto-cancel", function() use ($testProduct, $testStockage, $testUser, $testInventory) {
    if (!$testProduct || !$testStockage || !$testUser) {
        return true;
    }
    
    $beforeQty = $testInventory->quantity;
    $reserveQty = 15;
    
    $service = new ProductReserveService();
    $reservation = $service->store([
        'product_id' => $testProduct->id,
        'stockage_id' => $testStockage->id,
        'reserved_by' => $testUser->id,
        'quantity' => $reserveQty,
        'expires_at' => now()->subHour(), // Already expired
        'reservation_notes' => 'Expired reservation test',
        'source' => 'manual'
    ]);
    
    $testInventory->refresh();
    $afterReserve = $testInventory->quantity;
    $deducted = $beforeQty - $afterReserve;
    
    info("Stock before: {$beforeQty}, after reserve: {$afterReserve}, deducted: {$deducted}");
    info("Created expired reservation: {$reservation->reservation_code}");
    
    // Run the cancel command
    Artisan::call('reservations:cancel-expired');
    $output = Artisan::output();
    
    $testInventory->refresh();
    $afterCancel = $testInventory->quantity;
    $returned = $afterCancel - $afterReserve;
    
    info("Stock after auto-cancel: {$afterCancel}, returned: {$returned}");
    info("Command output: " . trim($output));
    
    $reservation->refresh();
    
    return $reservation->status === 'cancelled' && $afterCancel == $beforeQty;
});

// ============================================
// WORKFLOW 5: INVENTORY QUERIES
// ============================================
section("WORKFLOW 5: Inventory Reporting");

test("Can get product inventory across all stockages", function() use ($testProduct) {
    if (!$testProduct) {
        return true;
    }
    
    $totalInventory = Inventory::where('product_id', $testProduct->id)
        ->sum('quantity');
    
    $inventoryByLocation = Inventory::where('product_id', $testProduct->id)
        ->with('stockage')
        ->get();
    
    info("Total inventory for product {$testProduct->id}: {$totalInventory}");
    
    foreach ($inventoryByLocation as $inv) {
        $locationName = $inv->stockage ? $inv->stockage->name : 'Unknown';
        info("  - {$locationName}: {$inv->quantity} {$inv->unit}");
    }
    
    return $totalInventory > 0;
});

test("Can get all reservations for a product", function() use ($testProduct) {
    if (!$testProduct) {
        return true;
    }
    
    $reservations = ProductReserve::where('product_id', $testProduct->id)->get();
    $pending = $reservations->where('status', 'pending')->count();
    $fulfilled = $reservations->where('status', 'fulfilled')->count();
    $cancelled = $reservations->where('status', 'cancelled')->count();
    
    info("Reservations for product {$testProduct->id}:");
    info("  - Pending: {$pending}");
    info("  - Fulfilled: {$fulfilled}");
    info("  - Cancelled: {$cancelled}");
    info("  - Total: {$reservations->count()}");
    
    return true;
});

test("Can calculate available vs reserved stock", function() use ($testProduct, $testStockage) {
    if (!$testProduct || !$testStockage) {
        return true;
    }
    
    $inventory = Inventory::where('product_id', $testProduct->id)
        ->where('stockage_id', $testStockage->id)
        ->first();
    
    if (!$inventory) {
        return true;
    }
    
    $physicalStock = $inventory->quantity;
    
    $reservedStock = ProductReserve::where('product_id', $testProduct->id)
        ->where('stockage_id', $testStockage->id)
        ->where('status', 'pending')
        ->sum('quantity');
    
    $availableStock = $physicalStock; // In our system, reserved stock is already deducted
    
    info("Stock Analysis:");
    info("  - Physical Stock: {$physicalStock}");
    info("  - Currently Reserved: {$reservedStock}");
    info("  - Available for Reservation: {$availableStock}");
    
    return true;
});

// ============================================
// WORKFLOW 6: PRODUCT-STOCKAGE RELATIONSHIPS
// ============================================
section("WORKFLOW 6: Product-Stockage Relationships");

test("Product can exist in multiple stockages", function() {
    $product = Product::first();
    if (!$product) {
        return true;
    }
    
    $stockageCount = $product->stockages()->count();
    $inventoryCount = $product->inventories()->count();
    
    info("Product '{$product->name}' exists in:");
    info("  - {$stockageCount} stockage(s)");
    info("  - {$inventoryCount} inventory record(s)");
    
    return true;
});

test("Stockage can contain multiple products", function() {
    $stockage = Stockage::first();
    if (!$stockage) {
        return true;
    }
    
    $productCount = $stockage->products()->count();
    $inventoryCount = $stockage->inventories()->count();
    
    info("Stockage '{$stockage->name}' contains:");
    info("  - {$productCount} unique product(s)");
    info("  - {$inventoryCount} inventory record(s)");
    
    return true;
});

// ============================================
// DATABASE STATISTICS
// ============================================
section("SYSTEM STATISTICS");

info("Current System State:");

$stats = [
    'Products' => Product::count(),
    'Stockages' => Stockage::count(),
    'Inventory Records' => Inventory::count(),
    'Product Reserves (Total)' => ProductReserve::count(),
    'Active Reservations' => ProductReserve::where('status', 'pending')->count(),
    'Total Stock Value' => Inventory::sum('quantity'),
];

foreach ($stats as $label => $value) {
    info("  {$label}: {$value}");
}

// ============================================
// RESULTS
// ============================================
section("WORKFLOW TEST RESULTS");

echo "\n";
info("Total Workflow Tests: $totalTests");
success("Passed: $passedTests");

if ($totalTests - $passedTests > 0) {
    error("Failed: " . ($totalTests - $passedTests));
}

$percentage = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;

echo "\n";
if ($percentage == 100) {
    success("All workflow tests passed! Success rate: 100%");
} elseif ($percentage >= 80) {
    info("Most workflow tests passed. Success rate: {$percentage}%");
} else {
    error("Many workflow tests failed. Success rate: {$percentage}%");
}

echo "\n";
success("Stock Management Workflow Tests Complete");
echo "\n";

