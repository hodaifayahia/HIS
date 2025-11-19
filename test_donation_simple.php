<?php

// Bootstrap Laravel for testing
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\Caisse\FinancialTransactionService;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Caisse\FinancialTransaction;

try {
    echo "Testing donation functionality (verifying standalone donations)...\n\n";
    
    // Find an existing ficheNavetteItem
    $item = ficheNavetteItem::where('remaining_amount', '>', 0)->first();
    if (!$item) {
        echo "❌ No items with remaining amount found for testing\n";
        exit;
    }
    
    echo "Test Item: ID {$item->id}, Remaining: {$item->remaining_amount}\n\n";
    
    // Count donations before
    $donationsBefore = FinancialTransaction::where('transaction_type', 'donation')->count();
    $donationsAttachedBefore = FinancialTransaction::where('transaction_type', 'donation')
        ->whereNotNull('fiche_navette_item_id')->count();
    $donationsStandaloneBefore = FinancialTransaction::where('transaction_type', 'donation')
        ->whereNull('fiche_navette_item_id')->count();
    
    echo "Before test:\n";
    echo "- Total donations: {$donationsBefore}\n";
    echo "- Donations attached to items: {$donationsAttachedBefore}\n";  
    echo "- Standalone donations: {$donationsStandaloneBefore}\n\n";
    
    // Test overpayment with donation
    $service = new FinancialTransactionService();
    $result = $service->processOverpayment(
        $item->id,
        $item->ficheNavette->patient_id,
        1, // Cashier ID
        100.00, // Required
        120.00, // Paid (20 excess)
        'cash',
        'donate',
        'Test standalone donation'
    );
    
    // Count donations after
    $donationsAfter = FinancialTransaction::where('transaction_type', 'donation')->count();
    $donationsAttachedAfter = FinancialTransaction::where('transaction_type', 'donation')
        ->whereNotNull('fiche_navette_item_id')->count();
    $donationsStandaloneAfter = FinancialTransaction::where('transaction_type', 'donation')
        ->whereNull('fiche_navette_item_id')->count();
    
    echo "After test:\n";
    echo "- Total donations: {$donationsAfter}\n";
    echo "- Donations attached to items: {$donationsAttachedAfter}\n";
    echo "- Standalone donations: {$donationsStandaloneAfter}\n\n";
    
    // Verify donation details
    $donationTx = $result['donation_transaction'];
    echo "New donation transaction:\n";
    echo "- ID: {$donationTx->id}\n";
    echo "- Amount: {$donationTx->amount}\n";
    echo "- fiche_navette_item_id: " . ($donationTx->fiche_navette_item_id ?: 'NULL') . "\n";
    echo "- Notes: {$donationTx->notes}\n\n";
    
    // Results
    if ($donationTx->fiche_navette_item_id === null) {
        echo "✅ SUCCESS: Donation is standalone (not attached to specific item)\n";
    } else {
        echo "❌ ISSUE: Donation is still attached to item {$donationTx->fiche_navette_item_id}\n";
    }
    
    if ($donationsStandaloneAfter > $donationsStandaloneBefore) {
        echo "✅ SUCCESS: Standalone donation count increased\n";
    } else {
        echo "❌ ISSUE: Standalone donation count did not increase\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "\n🎯 Donation test completed!\n";
