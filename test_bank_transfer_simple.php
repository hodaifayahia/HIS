<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Bank Transfer Test - Simple Version ===\n";

try {
    // Get existing data from database
    $patient = \App\Models\Patient::first();
    $user = \App\Models\User::first();
    $bank = \App\Models\Bank\Bank::first();
    $ficheNavette = \App\Models\Reception\ficheNavette::first();
    $ficheNavetteItem = \App\Models\Reception\ficheNavetteItem::first();

    if (!$patient || !$user || !$bank || !$ficheNavette || !$ficheNavetteItem) {
        echo "❌ Missing required data in database:\n";
        echo "   Patient: " . ($patient ? "✓" : "✗") . "\n";
        echo "   User: " . ($user ? "✓" : "✗") . "\n";
        echo "   Bank: " . ($bank ? "✓" : "✗") . "\n";
        echo "   FicheNavette: " . ($ficheNavette ? "✓" : "✗") . "\n";
        echo "   FicheNavetteItem: " . ($ficheNavetteItem ? "✓" : "✗") . "\n";
        exit(1);
    }

    echo "✓ Found existing data:\n";
    echo "   Patient ID: {$patient->id}\n";
    echo "   User ID: {$user->id}\n";
    echo "   Bank ID: {$bank->id} - {$bank->name}\n";
    echo "   FicheNavette ID: {$ficheNavette->id}\n";
    echo "   FicheNavetteItem ID: {$ficheNavetteItem->id}\n\n";

    // Test data for bank transfer
    $testData = [
        'fiche_navette_id' => $ficheNavette->id,
        'patient_id' => $patient->id,
        'cashier_id' => $user->id,
        'payment_method' => 'bank_transfer',
        'transaction_type' => 'bulk_payment',
        'total_amount' => 100.00,
        'notes' => 'Test bank transfer payment',
        'is_bank_transaction' => true,
        'bank_id' => $bank->id,
        'items' => [
            [
                'fiche_navette_item_id' => $ficheNavetteItem->id,
                'amount' => 100.00,
                'remaining_amount' => 100.00,
                'item_name' => 'Test Item',
                'is_dependency' => false,
            ]
        ]
    ];

    echo "2. Testing FinancialTransactionService directly...\n";
    
    // Test the service method directly
    $service = new \App\Services\Caisse\FinancialTransactionService();
    $result = $service->createBulkPayments($testData);
    
    echo "✓ Service result:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

    // Check if transactions were created with bank info
    if (isset($result['payments']) && count($result['payments']) > 0) {
        echo "3. Verifying bank transaction data...\n";
        
        foreach ($result['payments'] as $payment) {
            echo "   Transaction ID: {$payment->id}\n";
            echo "   Payment Method: {$payment->payment_method}\n";
            echo "   Is Bank Transaction: " . ($payment->is_bank_transaction ? 'YES' : 'NO') . "\n";
            echo "   Bank ID: " . ($payment->bank_id ?? 'NULL') . "\n";
            
            if ($payment->is_bank_transaction && $payment->bank_id) {
                echo "   ✓ Bank transfer data saved correctly!\n";
            } else {
                echo "   ❌ Bank transfer data NOT saved correctly!\n";
                echo "   Expected: is_bank_transaction=true, bank_id={$bank->id}\n";
                echo "   Actual: is_bank_transaction=" . ($payment->is_bank_transaction ? 'true' : 'false') . ", bank_id=" . ($payment->bank_id ?? 'null') . "\n";
            }
        }
    }

    echo "\n4. Testing web route directly...\n";
    
    // Create a request
    $request = new \Illuminate\Http\Request();
    $request->replace($testData);
    $request->headers->set('Accept', 'application/json');

    echo "   Request data: " . json_encode($request->all(), JSON_PRETTY_PRINT) . "\n";

    // Test controller directly
    $service = new \App\Services\Caisse\FinancialTransactionService();
    $controller = new \App\Http\Controllers\Caisse\FinancialTransactionController($service);
    
    try {
        // Create a proper request instance
        $formRequest = new \App\Http\Requests\Caisse\BulkPaymentRequest();
        $formRequest->replace($request->all());
        $formRequest->setContainer(app());
        $formRequest->setMethod('POST');
        $formRequest->headers = $request->headers;
        
        $response = $controller->bulkPayment($formRequest);
        echo "   ✓ Controller response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
    } catch (\Exception $e) {
        echo "   ❌ Controller error: " . $e->getMessage() . "\n";
        echo "   Stack trace: " . $e->getTraceAsString() . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";