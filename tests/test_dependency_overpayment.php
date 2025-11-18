<?php

// Test dependency overpayment functionality
require_once 'vendor/autoload.php';

use App\Models\Reception\ItemDependency;
use App\Services\Caisse\FinancialTransactionService;

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Dependency Overpayment\n";
echo "==============================\n\n";

// Get the first dependency and set it up for testing
$dependency = ItemDependency::first();
if (! $dependency) {
    echo "No dependencies found\n";
    exit;
}

// Set up test data
$dependency->update([
    'final_price' => 200.00,
    'paid_amount' => 50.00,
    'remaining_amount' => 150.00,
    'payment_status' => 'pending',
]);

echo "Set up dependency ID: {$dependency->id}\n";
echo "Before - Final: {$dependency->final_price}, Paid: {$dependency->paid_amount}, Remaining: {$dependency->remaining_amount}\n\n";

// Test the service method with dependency
$service = new FinancialTransactionService;

try {
    $result = $service->processOverpayment(
        $dependency->parent_item_id, // Use parent item ID
        3, // patient_id (valid patient ID)
        1, // cashier_id
        100.0, // required_amount
        120.0, // paid_amount (overpayment of 20.0)
        'cash',
        'donate',
        'Test dependency overpayment',
        $dependency->id, // item_dependency_id
        $dependency->dependent_prestation_id  // dependent_prestation_id
    );

    echo "Dependency overpayment processed successfully!\n";
    echo 'Payment transaction ID: '.$result['payment_transaction']['id']."\n";
    echo 'Donation transaction ID: '.$result['donation_transaction']['id']."\n";
    echo 'Excess amount: '.$result['excess_amount']."\n\n";

    // Check the dependency again
    $dependency->refresh();
    echo "After - Final: {$dependency->final_price}, Paid: {$dependency->paid_amount}, Remaining: {$dependency->remaining_amount}\n";
    echo "Payment Status: {$dependency->payment_status}\n";

} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
    echo 'Stack trace: '.$e->getTraceAsString()."\n";
}
