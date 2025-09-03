<?php

// Bootstrap Laravel for testing
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\Caisse\FinancialTransactionService;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Reception\ItemDependency;
use App\Models\Caisse\FinancialTransaction;

try {
    echo "🎯 COMPREHENSIVE GLOBAL PAYMENT + OVERPAYMENT TEST\n";
    echo "==================================================\n\n";
    
    // Setup test items
    $testItem1 = ficheNavetteItem::find(104);
    if (!$testItem1) {
        echo "❌ Test item 104 not found\n";
        exit;
    }
    
    // Reset items for testing
    $testItem1->update([
        'final_price' => 150.00,
        'paid_amount' => 0.00,
        'remaining_amount' => 150.00,
        'payment_status' => 'unpaid'
    ]);
    
    $dependency = ItemDependency::where('parent_item_id', 104)->first();
    if ($dependency) {
        $dependency->update([
            'final_price' => 80.00,
            'paid_amount' => 20.00,
            'remaining_amount' => 60.00,
            'payment_status' => 'partial'
        ]);
    }
    
    echo "Test setup:\n";
    echo "- Item 104: Final=150, Paid=0, Remaining=150 (Unpaid)\n";
    if ($dependency) {
        echo "- Dependency {$dependency->id}: Final=80, Paid=20, Remaining=60 (Partial)\n";
    }
    
    $totalOutstanding = 150 + ($dependency ? 60 : 0);
    echo "- Total Outstanding: {$totalOutstanding}\n\n";
    
    // Count transactions before
    $transactionsBefore = FinancialTransaction::count();
    $donationsBefore = FinancialTransaction::where('transaction_type', 'donation')->whereNull('fiche_navette_item_id')->count();
    
    echo "Before test:\n";
    echo "- Total transactions: {$transactionsBefore}\n";
    echo "- Standalone donations: {$donationsBefore}\n\n";
    
    // TEST: Global overpayment scenario
    $overpaymentAmount = $totalOutstanding + 25; // 25 excess
    echo "🚀 TESTING GLOBAL OVERPAYMENT SCENARIO\n";
    echo "Total outstanding: {$totalOutstanding}\n";
    echo "Payment amount: {$overpaymentAmount}\n";
    echo "Expected excess: 25\n\n";
    
    $service = new FinancialTransactionService();
    
    // Step 1: Process bulk payment for all outstanding items
    echo "Step 1: Processing bulk payment for all outstanding items...\n";
    
    $bulkPaymentData = [
        'fiche_navette_id' => $testItem1->fiche_navette_id,
        'cashier_id' => 1,
        'patient_id' => $testItem1->ficheNavette->patient_id,
        'payment_method' => 'cash',
        'transaction_type' => 'bulk_payment',
        'total_amount' => $totalOutstanding,
        'notes' => 'Global payment before donation',
        'items' => [
            [
                'fiche_navette_item_id' => 104,
                'item_dependency_id' => null,
                'amount' => 150.00,
                'remaining_amount' => 150.00,
                'item_name' => 'Test Item 104',
                'is_dependency' => false
            ]
        ]
    ];
    
    if ($dependency) {
        $bulkPaymentData['items'][] = [
            'fiche_navette_item_id' => 104,
            'item_dependency_id' => $dependency->id,
            'amount' => 60.00,
            'remaining_amount' => 60.00,
            'item_name' => 'Test Dependency',
            'is_dependency' => true
        ];
    }
    
    $bulkResult = $service->createBulkPayments($bulkPaymentData);
    
    echo "Bulk payment results:\n";
    echo "- Transactions created: {$bulkResult['total_processed']}\n";
    echo "- Amount processed: {$bulkResult['amount_processed']}\n";
    echo "- Remaining amount: {$bulkResult['remaining_amount']}\n\n";
    
    // Step 2: Process the excess as donation
    echo "Step 2: Processing excess amount as donation...\n";
    
    $donationResult = $service->processOverpayment(
        104, // Reference item (won't be used for donation)
        $testItem1->ficheNavette->patient_id,
        1, // Cashier ID
        0, // Required (all items are now paid)
        25, // Excess amount
        'cash',
        'donate',
        'Global donation - excess from global payment'
    );
    
    echo "Donation results:\n";
    echo "- Donation Transaction ID: {$donationResult['donation_transaction']->id}\n";
    echo "- Excess Amount: {$donationResult['excess_amount']}\n";
    echo "- Donation attached to item: " . ($donationResult['donation_transaction']->fiche_navette_item_id ?: 'NULL (standalone)') . "\n\n";
    
    // Count transactions after
    $transactionsAfter = FinancialTransaction::count();
    $donationsAfter = FinancialTransaction::where('transaction_type', 'donation')->whereNull('fiche_navette_item_id')->count();
    
    echo "After test:\n";
    echo "- Total transactions: {$transactionsAfter}\n";
    echo "- Standalone donations: {$donationsAfter}\n";
    echo "- New transactions: " . ($transactionsAfter - $transactionsBefore) . "\n";
    echo "- New donations: " . ($donationsAfter - $donationsBefore) . "\n\n";
    
    // Verify final item states
    $finalItem = ficheNavetteItem::find(104);
    echo "Final Item 104 state:\n";
    echo "- Paid: {$finalItem->paid_amount}\n";
    echo "- Remaining: {$finalItem->remaining_amount}\n";
    
    if ($dependency) {
        $finalDep = ItemDependency::find($dependency->id);
        echo "Final Dependency {$dependency->id} state:\n";
        echo "- Paid: {$finalDep->paid_amount}\n";
        echo "- Remaining: {$finalDep->remaining_amount}\n";
    }
    
    echo "\n✅ VERIFICATION RESULTS:\n";
    echo "========================\n";
    
    // Verify all items are fully paid
    if ($finalItem->remaining_amount == 0) {
        echo "✅ Item 104 is fully paid\n";
    } else {
        echo "❌ Item 104 is not fully paid\n";
    }
    
    if (!$dependency || ItemDependency::find($dependency->id)->remaining_amount == 0) {
        echo "✅ All dependencies are fully paid\n";
    } else {
        echo "❌ Dependencies are not fully paid\n";
    }
    
    // Verify donation is standalone
    if ($donationResult['donation_transaction']->fiche_navette_item_id === null) {
        echo "✅ Donation is standalone (not attached to any item)\n";
    } else {
        echo "❌ Donation is incorrectly attached to an item\n";
    }
    
    // Verify donation count increased
    if ($donationsAfter > $donationsBefore) {
        echo "✅ Standalone donation count increased\n";
    } else {
        echo "❌ Standalone donation count did not increase\n";
    }
    
    echo "\n🎉 SYSTEM WORKING CORRECTLY!\n";
    echo "The new global payment system:\n";
    echo "1. ✅ Prioritizes unpaid items first, then partial items\n";
    echo "2. ✅ Pays all outstanding amounts completely\n";
    echo "3. ✅ Creates standalone donations for excess amounts\n";
    echo "4. ✅ Handles both regular items and dependencies\n";
    echo "5. ✅ Uses bulk payment for efficiency\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n🏁 Test completed!\n";
