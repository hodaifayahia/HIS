<?php

// Test overpayment handling to make sure donations don't go to first item
require_once 'vendor/autoload.php';

use App\Services\Caisse\FinancialTransactionService;

try {
    // Test: Create an overpayment scenario and verify donation doesn't attach to any specific item
    $service = new FinancialTransactionService;

    echo "Testing overpayment donation handling...\n\n";

    // Find an existing ficheNavetteItem for testing
    $item = \App\Models\Reception\ficheNavetteItem::where('remaining_amount', '>', 0)->first();
    if (! $item) {
        echo "❌ No items with remaining amount found for testing\n";
        exit;
    }

    echo "Test Item: ID {$item->id}, Remaining: {$item->remaining_amount}\n\n";

    // Test overpayment with donation
    $requiredAmount = 100.00;
    $paidAmount = 120.00; // 20 excess
    $excessAmount = $paidAmount - $requiredAmount;

    echo "Payment scenario:\n";
    echo "- Required: {$requiredAmount}\n";
    echo "- Paid: {$paidAmount}\n";
    echo "- Excess: {$excessAmount}\n\n";

    // Count transactions before
    $transactionsBefore = \App\Models\Caisse\FinancialTransaction::count();
    $donationsBefore = \App\Models\Caisse\FinancialTransaction::where('transaction_type', 'donation')->count();

    // Process overpayment with donation
    $result = $service->processOverpayment(
        $item->id,
        $item->ficheNavette->patient_id,
        1, // Cashier ID
        $requiredAmount,
        $paidAmount,
        'cash',
        'donate',
        'Test overpayment donation',
        null, // No item dependency
        null  // No dependent prestation
    );

    echo "Overpayment processed successfully!\n";
    echo "Results:\n";
    echo "- Payment Transaction ID: {$result['payment_transaction']->id}\n";
    echo "- Donation Transaction ID: {$result['donation_transaction']->id}\n";
    echo "- Excess Amount: {$result['excess_amount']}\n\n";

    // Check donation transaction details
    $donationTx = $result['donation_transaction'];
    echo "Donation Transaction Details:\n";
    echo "- ID: {$donationTx->id}\n";
    echo "- Amount: {$donationTx->amount}\n";
    echo "- Type: {$donationTx->transaction_type}\n";
    echo '- Attached to fiche_navette_item_id: '.($donationTx->fiche_navette_item_id ?: 'NULL (standalone)')."\n";
    echo "- Patient ID: {$donationTx->patient_id}\n";
    echo "- Notes: {$donationTx->notes}\n\n";

    // Verify counts
    $transactionsAfter = \App\Models\Caisse\FinancialTransaction::count();
    $donationsAfter = \App\Models\Caisse\FinancialTransaction::where('transaction_type', 'donation')->count();

    echo "Transaction counts:\n";
    echo "- Before: {$transactionsBefore} total, {$donationsBefore} donations\n";
    echo "- After: {$transactionsAfter} total, {$donationsAfter} donations\n";
    echo '- New transactions: '.($transactionsAfter - $transactionsBefore)."\n";
    echo '- New donations: '.($donationsAfter - $donationsBefore)."\n\n";

    // Verify donation is standalone (not attached to any specific item)
    if ($donationTx->fiche_navette_item_id === null) {
        echo "✅ SUCCESS: Donation transaction is standalone (not attached to any specific item)\n";
    } else {
        echo "❌ ISSUE: Donation transaction is still attached to fiche_navette_item_id: {$donationTx->fiche_navette_item_id}\n";
    }

    // Verify payment transaction updated the item
    $updatedItem = \App\Models\Reception\ficheNavetteItem::find($item->id);
    echo "\nItem update verification:\n";
    echo "- Original paid: {$item->paid_amount}, remaining: {$item->remaining_amount}\n";
    echo "- Updated paid: {$updatedItem->paid_amount}, remaining: {$updatedItem->remaining_amount}\n";

    if ($updatedItem->paid_amount > $item->paid_amount) {
        echo "✅ SUCCESS: Item payment amounts were updated correctly\n";
    } else {
        echo "❌ ISSUE: Item payment amounts were not updated\n";
    }

} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
    echo "Stack trace:\n".$e->getTraceAsString()."\n";
}

echo "\n🎯 Overpayment donation test completed!\n";
