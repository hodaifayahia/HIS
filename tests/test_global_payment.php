<?php

// Test script for global payment prioritization
require_once 'vendor/autoload.php';

// Mock data to test the sorting logic
$items = [
    ['id' => 1, 'name' => 'Item 1 - Fully Paid', 'final_price' => 100, 'paid_amount' => 100, 'remaining_amount' => 0],
    ['id' => 2, 'name' => 'Item 2 - Partial', 'final_price' => 200, 'paid_amount' => 50, 'remaining_amount' => 150],
    ['id' => 3, 'name' => 'Item 3 - Unpaid', 'final_price' => 150, 'paid_amount' => 0, 'remaining_amount' => 150],
    ['id' => 4, 'name' => 'Item 4 - Partial', 'final_price' => 300, 'paid_amount' => 100, 'remaining_amount' => 200],
    ['id' => 5, 'name' => 'Item 5 - Unpaid', 'final_price' => 80, 'paid_amount' => 0, 'remaining_amount' => 80],
];

echo "Original items:\n";
foreach ($items as $item) {
    echo "- {$item['name']}: Final: {$item['final_price']}, Paid: {$item['paid_amount']}, Remaining: {$item['remaining_amount']}\n";
}

// Sort items by priority: unpaid first, then partial, then fully paid
usort($items, function ($a, $b) {
    $aRemaining = $a['remaining_amount'];
    $aPaid = $a['paid_amount'];
    $bRemaining = $b['remaining_amount'];
    $bPaid = $b['paid_amount'];

    // Priority 1: Items with remaining amount (unpaid/partial)
    if ($aRemaining > 0 && $bRemaining === 0) {
        return -1;
    }
    if ($bRemaining > 0 && $aRemaining === 0) {
        return 1;
    }

    // Priority 2: Among unpaid items, prioritize by paid status (unpaid before partial)
    if ($aRemaining > 0 && $bRemaining > 0) {
        if ($aPaid === 0 && $bPaid > 0) {
            return -1;
        }  // Unpaid before partial
        if ($bPaid === 0 && $aPaid > 0) {
            return 1;
        }   // Unpaid before partial
    }

    return 0;
});

echo "\nSorted items (priority order):\n";
foreach ($items as $item) {
    $status = $item['remaining_amount'] > 0 ? ($item['paid_amount'] > 0 ? 'Partial' : 'Unpaid') : 'Paid';
    echo "- {$item['name']} ({$status}): Remaining: {$item['remaining_amount']}\n";
}

// Test allocation logic
$globalPaymentAmount = 400;
$remainingToApply = $globalPaymentAmount;
$totalOutstanding = array_sum(array_column($items, 'remaining_amount'));

echo "\nGlobal payment allocation test:\n";
echo "Payment Amount: {$globalPaymentAmount}\n";
echo "Total Outstanding: {$totalOutstanding}\n";

if ($globalPaymentAmount > $totalOutstanding) {
    echo 'OVERPAYMENT DETECTED: Excess = '.($globalPaymentAmount - $totalOutstanding)."\n";
}

echo "\nAllocation plan:\n";
foreach ($items as $item) {
    $remaining = $item['remaining_amount'];
    if ($remaining > 0 && $remainingToApply > 0) {
        $toApply = min($remaining, $remainingToApply);
        echo "- {$item['name']}: Pay {$toApply} of {$remaining} remaining\n";
        $remainingToApply -= $toApply;

        if ($remainingToApply <= 0) {
            echo "  → Payment fully allocated\n";
            break;
        }
    }
}

if ($remainingToApply > 0) {
    echo "Remaining amount after allocation: {$remainingToApply}\n";
}

echo "\n✅ Global payment prioritization test completed!\n";
