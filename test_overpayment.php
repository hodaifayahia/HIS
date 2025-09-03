<?php

// Simple test script to debug overpayment functionality
require_once 'vendor/autoload.php';

use App\Services\Caisse\FinancialTransactionService;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Reception\ItemDependency;

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Overpayment Functionality\n";
echo "================================\n\n";

// Check if we have any ficheNavetteItems
$ficheItems = ficheNavetteItem::take(5)->get();
echo "Found " . $ficheItems->count() . " ficheNavetteItems\n";

foreach ($ficheItems as $item) {
    echo "ID: {$item->id}, Final Price: {$item->final_price}, Paid: {$item->paid_amount}, Remaining: {$item->remaining_amount}\n";
}

echo "\n";

// Check if we have any ItemDependencies
$dependencies = ItemDependency::take(5)->get();
echo "Found " . $dependencies->count() . " ItemDependencies\n";

foreach ($dependencies as $dep) {
    echo "ID: {$dep->id}, Final Price: {$dep->final_price}, Paid: {$dep->paid_amount}, Remaining: {$dep->remaining_amount}\n";
}

echo "\n";

// Test the service method
$service = new FinancialTransactionService();

// Find a suitable item to test with
$testItem = ficheNavetteItem::where('remaining_amount', '>', 0)->first();
if ($testItem) {
    echo "Testing with ficheNavetteItem ID: {$testItem->id}\n";
    echo "Before - Paid: {$testItem->paid_amount}, Remaining: {$testItem->remaining_amount}\n";
    
    try {
        $result = $service->processOverpayment(
            $testItem->id,
            $testItem->ficheNavette->patient_id ?? 1,
            1, // cashier_id
            10.0, // required_amount
            15.0, // paid_amount (overpayment of 5.0)
            'cash',
            'donate',
            'Test overpayment',
            null, // item_dependency_id
            null  // dependent_prestation_id
        );
        
        echo "Overpayment processed successfully!\n";
        echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
        
        // Check the item again
        $testItem->refresh();
        echo "After - Paid: {$testItem->paid_amount}, Remaining: {$testItem->remaining_amount}\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "No suitable test item found\n";
}
