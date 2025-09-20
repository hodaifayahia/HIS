<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Stock Movement Approval Functionality...\n\n";

// Test StockMovementItem methods
$item = new App\Models\StockMovementItem();

echo "StockMovementItem methods:\n";
echo "- isApproved(): " . (method_exists($item, 'isApproved') ? '✓' : '✗') . "\n";
echo "- isRejected(): " . (method_exists($item, 'isRejected') ? '✓' : '✗') . "\n";
echo "- isPending(): " . (method_exists($item, 'isPending') ? '✓' : '✗') . "\n";
echo "- getStatus(): " . (method_exists($item, 'getStatus') ? '✓' : '✗') . "\n";
echo "- isEditable(): " . (method_exists($item, 'isEditable') ? '✓' : '✗') . "\n\n";

// Test service resolution
try {
    $service = app(App\Services\StockMovementApprovalService::class);
    echo "StockMovementApprovalService: ✓ Can be resolved\n";
} catch (Exception $e) {
    echo "StockMovementApprovalService: ✗ Cannot be resolved - " . $e->getMessage() . "\n";
}

// Test events exist
echo "\nEvents:\n";
echo "- StockMovementItemApproved: " . (class_exists('App\\Events\\StockMovementItemApproved') ? '✓' : '✗') . "\n";
echo "- StockMovementItemRejected: " . (class_exists('App\\Events\\StockMovementItemRejected') ? '✓' : '✗') . "\n";

// Test listener exists
echo "\nListener:\n";
echo "- LogStockMovementAudit: " . (class_exists('App\\Listeners\\LogStockMovementAudit') ? '✓' : '✗') . "\n";

// Test form requests exist
echo "\nForm Requests:\n";
echo "- ApproveItemsRequest: " . (class_exists('App\\Http\\Requests\\Stock\\ApproveItemsRequest') ? '✓' : '✗') . "\n";
echo "- RejectItemsRequest: " . (class_exists('App\\Http\\Requests\\Stock\\RejectItemsRequest') ? '✓' : '✗') . "\n";

// Test resource exists
echo "\nResource:\n";
echo "- StockMovementItemResource: " . (class_exists('App\\Http\\Resources\\Stock\\StockMovementItemResource') ? '✓' : '✗') . "\n";

echo "\n=== Test Complete ===\n";