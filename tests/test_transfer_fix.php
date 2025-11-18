<?php

/**
 * Transfer Initialization Verification Script
 *
 * Tests the fixed transfer initialization workflow
 * Run: php artisan tinker < test_transfer_fix.php
 */

namespace Tests;

use App\Models\PharmacyMovement;
use App\Models\PharmacyMovementItem;
use Illuminate\Support\Facades\DB;

echo "===== TRANSFER INITIALIZATION FIX VERIFICATION =====\n\n";

// Test 1: Check for movements in "approved" status with approved items
echo "[TEST 1] Checking for approved movements with approved items...\n";
$approvedMovements = PharmacyMovement::where('status', 'approved')
    ->with(['items' => function ($query) {
        $query->where('approved_quantity', '>', 0);
    }])
    ->get();

echo 'Found '.$approvedMovements->count()." approved movements\n";
foreach ($approvedMovements as $movement) {
    $approvedItemsCount = $movement->items->count();
    echo "  - Movement #{$movement->movement_number} with {$approvedItemsCount} approved items\n";
    foreach ($movement->items as $item) {
        echo "    • Product: {$item->product->name}, Approved Qty: {$item->approved_quantity}\n";
    }
}
echo "\n";

// Test 2: Check enum values in database
echo "[TEST 2] Checking database enum values...\n";
$enumValues = DB::select("
    SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'pharmacy_stock_movements' 
    AND COLUMN_NAME = 'status'
");

if (! empty($enumValues)) {
    echo 'Status enum values: '.$enumValues[0]->COLUMN_TYPE."\n";
    // Should contain 'in_transfer' not 'in_transit'
    if (strpos($enumValues[0]->COLUMN_TYPE, "'in_transfer'") !== false) {
        echo "✓ Correct: 'in_transfer' is valid\n";
    } else {
        echo "✗ ERROR: 'in_transfer' not found in enum\n";
    }
}
echo "\n";

// Test 3: Check recent movements in in_transfer status
echo "[TEST 3] Checking movements successfully in 'in_transfer' status...\n";
$inTransferMovements = PharmacyMovement::where('status', 'in_transfer')
    ->with('items')
    ->orderBy('transfer_initiated_at', 'desc')
    ->limit(5)
    ->get();

echo 'Found '.$inTransferMovements->count()." movements in 'in_transfer' status\n";
foreach ($inTransferMovements as $movement) {
    $initiatedBy = $movement->transfer_initiated_by
        ? "User ID: {$movement->transfer_initiated_by}"
        : 'NOT SET';
    echo "  - Movement #{$movement->movement_number}\n";
    echo "    Initiated At: {$movement->transfer_initiated_at}\n";
    echo "    Initiated By: {$initiatedBy}\n";
    echo '    Items Count: '.$movement->items->count()."\n";
}
echo "\n";

// Test 4: Check audit logs for transfer_initiated action
echo "[TEST 4] Checking audit logs for transfer initialization...\n";
$auditLogs = DB::table('pharmacy_movement_audit_logs')
    ->where('action', 'transfer_initiated')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo 'Found '.$auditLogs->count()." transfer_initiated audit logs\n";
foreach ($auditLogs as $log) {
    echo "  - Movement ID: {$log->pharmacy_stock_movement_id}\n";
    echo "    Action: {$log->action}\n";
    echo "    Qty Changed: {$log->quantity_changed}\n";
    echo "    By User: {$log->user_id}\n";
    echo "    At: {$log->created_at}\n";
}
echo "\n";

// Test 5: Check item status transitions
echo "[TEST 5] Checking item status transitions...\n";
$itemsByStatus = PharmacyMovementItem::groupBy('status')
    ->selectRaw('status, COUNT(*) as count')
    ->get();

echo "Items by status:\n";
foreach ($itemsByStatus as $item) {
    echo "  - {$item->status}: {$item->count}\n";
}
echo "\n";

// Test 6: Verify provided_quantity is set when items are in_transfer
echo "[TEST 6] Checking provided_quantity for in_transfer items...\n";
$inTransferItems = PharmacyMovementItem::where('status', 'in_transfer')
    ->where('provided_quantity', 0)
    ->count();

if ($inTransferItems === 0) {
    echo "✓ All in_transfer items have provided_quantity set\n";
} else {
    echo "✗ WARNING: {$inTransferItems} in_transfer items have provided_quantity = 0\n";
}
echo "\n";

// Test 7: Data integrity check
echo "[TEST 7] Data integrity checks...\n";
$orphaned = DB::select('
    SELECT COUNT(*) as count FROM pharmacy_stock_movement_items 
    WHERE pharmacy_movement_id NOT IN 
    (SELECT id FROM pharmacy_stock_movements)
');

if ($orphaned[0]->count === 0) {
    echo "✓ No orphaned items\n";
} else {
    echo "✗ Found {$orphaned[0]->count} orphaned items\n";
}

$invalidStatus = DB::select("
    SELECT COUNT(*) as count FROM pharmacy_stock_movements 
    WHERE status NOT IN ('draft', 'pending', 'approved', 'rejected', 'in_transfer', 'completed', 'cancelled')
");

if ($invalidStatus[0]->count === 0) {
    echo "✓ All movements have valid status values\n";
} else {
    echo "✗ Found {$invalidStatus[0]->count} movements with invalid status\n";
}
echo "\n";

// Test 8: Summary statistics
echo "[TEST 8] System Statistics...\n";
$stats = [
    'total_movements' => PharmacyMovement::count(),
    'draft' => PharmacyMovement::where('status', 'draft')->count(),
    'pending' => PharmacyMovement::where('status', 'pending')->count(),
    'approved' => PharmacyMovement::where('status', 'approved')->count(),
    'in_transfer' => PharmacyMovement::where('status', 'in_transfer')->count(),
    'completed' => PharmacyMovement::where('status', 'completed')->count(),
    'rejected' => PharmacyMovement::where('status', 'rejected')->count(),
    'cancelled' => PharmacyMovement::where('status', 'cancelled')->count(),
    'total_items' => PharmacyMovementItem::count(),
];

echo "Total Movements: {$stats['total_movements']}\n";
echo "  Draft: {$stats['draft']}\n";
echo "  Pending: {$stats['pending']}\n";
echo "  Approved: {$stats['approved']}\n";
echo "  In Transfer: {$stats['in_transfer']}\n";
echo "  Completed: {$stats['completed']}\n";
echo "  Rejected: {$stats['rejected']}\n";
echo "  Cancelled: {$stats['cancelled']}\n";
echo "Total Items: {$stats['total_items']}\n";
echo "\n";

echo "===== VERIFICATION COMPLETE =====\n";
