<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Stock Movement Enhancements Verification ===\n\n";

// 1. Check if service can be resolved
try {
    $service = app(\App\Services\StockMovementApprovalService::class);
    echo "✓ StockMovementApprovalService can be resolved\n";
} catch (Exception $e) {
    echo "✗ StockMovementApprovalService resolution failed: " . $e->getMessage() . "\n";
}

// 2. Check if events exist
try {
    if (class_exists('\App\Events\StockMovementItemApproved')) {
        echo "✓ StockMovementItemApproved event exists\n";
    } else {
        echo "✗ StockMovementItemApproved event does not exist\n";
    }
} catch (Exception $e) {
    echo "✗ StockMovementItemApproved event failed: " . $e->getMessage() . "\n";
}

try {
    if (class_exists('\App\Events\StockMovementItemRejected')) {
        echo "✓ StockMovementItemRejected event exists\n";
    } else {
        echo "✗ StockMovementItemRejected event does not exist\n";
    }
} catch (Exception $e) {
    echo "✗ StockMovementItemRejected event failed: " . $e->getMessage() . "\n";
}

// 3. Check if audit log model exists
try {
    $auditLog = new \App\Models\StockMovementAuditLog();
    echo "✓ StockMovementAuditLog model exists\n";
} catch (Exception $e) {
    echo "✗ StockMovementAuditLog model failed: " . $e->getMessage() . "\n";
}

// 4. Check if listener exists
try {
    if (class_exists('\App\Listeners\LogStockMovementAudit')) {
        echo "✓ LogStockMovementAudit listener exists\n";
    } else {
        echo "✗ LogStockMovementAudit listener does not exist\n";
    }
} catch (Exception $e) {
    echo "✗ LogStockMovementAudit listener failed: " . $e->getMessage() . "\n";
}

// 5. Check if service provider exists
try {
    if (class_exists('\App\Providers\StockMovementEventServiceProvider')) {
        echo "✓ StockMovementEventServiceProvider exists\n";
    } else {
        echo "✗ StockMovementEventServiceProvider does not exist\n";
    }
} catch (Exception $e) {
    echo "✗ StockMovementEventServiceProvider failed: " . $e->getMessage() . "\n";
}

// 6. Check if form requests exist
try {
    if (class_exists('\App\Http\Requests\Stock\ApproveItemsRequest')) {
        echo "✓ ApproveItemsRequest exists\n";
    } else {
        echo "✗ ApproveItemsRequest does not exist\n";
    }
} catch (Exception $e) {
    echo "✗ ApproveItemsRequest failed: " . $e->getMessage() . "\n";
}

try {
    if (class_exists('\App\Http\Requests\Stock\RejectItemsRequest')) {
        echo "✓ RejectItemsRequest exists\n";
    } else {
        echo "✗ RejectItemsRequest does not exist\n";
    }
} catch (Exception $e) {
    echo "✗ RejectItemsRequest failed: " . $e->getMessage() . "\n";
}

// 7. Check if resource exists
try {
    if (class_exists('\App\Http\Resources\Stock\StockMovementItemResource')) {
        echo "✓ StockMovementItemResource exists\n";
    } else {
        echo "✗ StockMovementItemResource does not exist\n";
    }
} catch (Exception $e) {
    echo "✗ StockMovementItemResource failed: " . $e->getMessage() . "\n";
}

// 8. Check database table exists
try {
    $exists = \Illuminate\Support\Facades\Schema::hasTable('stock_movement_audit_logs');
    if ($exists) {
        echo "✓ stock_movement_audit_logs table exists\n";
    } else {
        echo "✗ stock_movement_audit_logs table does not exist\n";
    }
} catch (Exception $e) {
    echo "✗ Database check failed: " . $e->getMessage() . "\n";
}

// 9. Check if StockMovementItem has new methods
try {
    $item = new \App\Models\StockMovementItem();
    if (method_exists($item, 'isApproved') && 
        method_exists($item, 'isRejected') && 
        method_exists($item, 'isPending') && 
        method_exists($item, 'isEditable') && 
        method_exists($item, 'getStatus')) {
        echo "✓ StockMovementItem has all new status methods\n";
    } else {
        echo "✗ StockMovementItem missing some status methods\n";
    }
} catch (Exception $e) {
    echo "✗ StockMovementItem check failed: " . $e->getMessage() . "\n";
}

echo "\n=== Verification Complete ===\n";