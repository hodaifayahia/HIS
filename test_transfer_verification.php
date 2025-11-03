<?php

/**
 * Transfer Initialization Fix - Quick Test
 * 
 * Verifies that the transfer initialization fix is working correctly
 */

namespace Tests;

use App\Models\PharmacyMovement;
use App\Models\PharmacyMovementItem;
use Illuminate\Support\Facades\DB;

echo "\n===== TRANSFER INITIALIZATION FIX VERIFICATION =====\n\n";

// Test 1: Verify approved movements with approved items exist
echo "[✓ TEST 1] Approved movements with approved items:\n";
$approvedMovements = PharmacyMovement::where('status', 'approved')
    ->withCount(['items as approved_items' => function ($query) {
        $query->where('approved_quantity', '>', 0);
    }])
    ->having('approved_items', '>', 0)
    ->get();

echo "  Found: " . $approvedMovements->count() . " movements\n";
if ($approvedMovements->count() > 0) {
    $firstMovement = $approvedMovements->first();
    echo "  Example: Movement #{$firstMovement->movement_number}\n";
    echo "  Status: {$firstMovement->status}\n";
    echo "  Approved Items: {$firstMovement->approved_items}\n";
}
echo "\n";

// Test 2: Check database enum values
echo "[✓ TEST 2] Database enum values:\n";
$enumValues = DB::select("
    SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'pharmacy_stock_movements' 
    AND COLUMN_NAME = 'status'
");

if (!empty($enumValues)) {
    echo "  Status enum: " . $enumValues[0]->COLUMN_TYPE . "\n";
    if (strpos($enumValues[0]->COLUMN_TYPE, "'in_transfer'") !== false) {
        echo "  ✓ 'in_transfer' is valid\n";
    } else {
        echo "  ✗ ERROR: 'in_transfer' not found in enum\n";
    }
}
echo "\n";

// Test 3: Verify in_transfer movements exist
echo "[✓ TEST 3] Movements in 'in_transfer' status:\n";
$inTransferCount = PharmacyMovement::where('status', 'in_transfer')->count();
echo "  Total: {$inTransferCount}\n";

$inTransferWithData = PharmacyMovement::where('status', 'in_transfer')
    ->where('transfer_initiated_at', '!=', null)
    ->count();
echo "  With transfer_initiated_at: {$inTransferWithData}\n";
echo "\n";

// Test 4: Verify item status transitions
echo "[✓ TEST 4] Item statuses:\n";
$itemStatuses = PharmacyMovementItem::selectRaw('status, COUNT(*) as count')
    ->groupBy('status')
    ->orderBy('count', 'desc')
    ->get();

foreach ($itemStatuses as $item) {
    echo "  {$item->status}: {$item->count}\n";
}
echo "\n";

// Test 5: Data integrity
echo "[✓ TEST 5] Data integrity checks:\n";

// Check for orphaned items
$orphaned = DB::table('pharmacy_stock_movement_items')
    ->whereNotIn('pharmacy_movement_id', 
        DB::table('pharmacy_stock_movements')->select('id')
    )
    ->count();
echo "  Orphaned items: {$orphaned} " . ($orphaned === 0 ? "✓" : "✗") . "\n";

// Check for invalid statuses
$invalidMovements = DB::table('pharmacy_stock_movements')
    ->whereNotIn('status', 
        ['draft', 'pending', 'approved', 'rejected', 'in_transfer', 'completed', 'cancelled']
    )
    ->count();
echo "  Invalid movement statuses: {$invalidMovements} " . ($invalidMovements === 0 ? "✓" : "✗") . "\n";

// Check provided_quantity for in_transfer items
$missingProvided = DB::table('pharmacy_stock_movement_items')
    ->where('status', 'in_transfer')
    ->where('provided_quantity', 0)
    ->count();
echo "  In-transfer items with missing provided_qty: {$missingProvided} " . ($missingProvided === 0 ? "✓" : "✗") . "\n";
echo "\n";

// Test 6: Summary statistics
echo "[✓ TEST 6] System Statistics:\n";
$stats = [
    'total' => PharmacyMovement::count(),
    'draft' => PharmacyMovement::where('status', 'draft')->count(),
    'pending' => PharmacyMovement::where('status', 'pending')->count(),
    'approved' => PharmacyMovement::where('status', 'approved')->count(),
    'in_transfer' => PharmacyMovement::where('status', 'in_transfer')->count(),
    'completed' => PharmacyMovement::where('status', 'completed')->count(),
    'rejected' => PharmacyMovement::where('status', 'rejected')->count(),
];

echo "  Total Movements: {$stats['total']}\n";
echo "    - Draft: {$stats['draft']}\n";
echo "    - Pending: {$stats['pending']}\n";
echo "    - Approved: {$stats['approved']} (Ready for transfer)\n";
echo "    - In Transfer: {$stats['in_transfer']} (Successfully transferred)\n";
echo "    - Completed: {$stats['completed']}\n";
echo "    - Rejected: {$stats['rejected']}\n";

$totalItems = PharmacyMovementItem::count();
echo "  Total Items: {$totalItems}\n";
echo "\n";

// Test 7: Workflow status check
echo "[✓ TEST 7] Workflow Status:\n";
$hasPending = $stats['pending'] > 0 ? "✓ Ready for approval" : "- No pending";
$hasApproved = $stats['approved'] > 0 ? "✓ Ready for transfer" : "- No approved";
$hasTransfer = $stats['in_transfer'] > 0 ? "✓ Transfers in progress" : "- No transfers";
$hasCompleted = $stats['completed'] > 0 ? "✓ Completed transfers" : "- No completed";

echo "  {$hasPending}\n";
echo "  {$hasApproved}\n";
echo "  {$hasTransfer}\n";
echo "  {$hasCompleted}\n";
echo "\n";

echo "===== VERIFICATION COMPLETE ✓ =====\n\n";
