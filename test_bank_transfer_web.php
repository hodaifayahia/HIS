 <?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Patient;
use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Models\Bank\Bank;
use App\Services\Caisse\FinancialTransactionService;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Bank Transfer Test - Web Route ===\n";

try {
    // 1. Create test data
    echo "1. Creating test data...\n";
    
    $patient = Patient::create([
        'first_name' => 'Test',
        'last_name' => 'Patient',
        'date_of_birth' => '1990-01-01',
        'gender' => 1,
        'phone' => '1234567890',
        'email' => 'test@example.com'
    ]);
    echo "   Created patient: {$patient->id}\n";

    $ficheNavette = ficheNavette::create([
        'patient_id' => $patient->id,
        'status' => 'pending',
        'total_amount' => 1000.00,
        'created_by' => 1
    ]);
    echo "   Created fiche navette: {$ficheNavette->id}\n";

    $ficheNavetteItem = ficheNavetteItem::create([
        'fiche_navette_id' => $ficheNavette->id,
        'prestation_id' => 1,
        'quantity' => 1,
        'unit_price' => 1000.00,
        'total_price' => 1000.00,
        'status' => 'pending'
    ]);
    echo "   Created fiche navette item: {$ficheNavetteItem->id}\n";

    // 2. Get a bank for testing
    $bank = Bank::first();
    if (!$bank) {
        $bank = Bank::create([
            'name' => 'Test Bank',
            'code' => 'TB001',
            'swift_code' => 'TESTBANK',
            'is_active' => true
        ]);
    }
    echo "   Using bank: {$bank->id} - {$bank->name}\n";

    // 3. Test the FinancialTransactionService directly
    echo "\n2. Testing FinancialTransactionService directly...\n";
    
    $service = new FinancialTransactionService();
    
    $bulkPaymentData = [
        'fiche_navette_id' => $ficheNavette->id,
        'patient_id' => $patient->id,
        'payment_method' => 'bank_transfer',
        'total_amount' => 1000.00,
        'is_bank_transaction' => true,
        'bank_id' => $bank->id,
        'items' => [
            [
                'fiche_navette_item_id' => $ficheNavetteItem->id,
                'amount' => 1000.00
            ]
        ]
    ];

    $result = $service->createBulkPayments($bulkPaymentData);
    echo "   Service result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";

    // 4. Check if the transaction was saved with bank details
    echo "\n3. Checking saved transaction...\n";
    
    $transaction = \App\Models\Caisse\FinancialTransaction::where('fiche_navette_id', $ficheNavette->id)->first();
    
    if ($transaction) {
        echo "   Transaction ID: {$transaction->id}\n";
        echo "   Payment Method: {$transaction->payment_method}\n";
        echo "   Is Bank Transaction: " . ($transaction->is_bank_transaction ? 'true' : 'false') . "\n";
        echo "   Bank ID: " . ($transaction->bank_id ?? 'null') . "\n";
        echo "   Amount: {$transaction->amount}\n";
        
        if ($transaction->is_bank_transaction && $transaction->bank_id) {
            echo "   ✅ Bank transfer details saved correctly!\n";
        } else {
            echo "   ❌ Bank transfer details NOT saved correctly!\n";
            echo "   Expected: is_bank_transaction=true, bank_id={$bank->id}\n";
            echo "   Actual: is_bank_transaction=" . ($transaction->is_bank_transaction ? 'true' : 'false') . ", bank_id=" . ($transaction->bank_id ?? 'null') . "\n";
        }
    } else {
        echo "   ❌ No transaction found!\n";
    }

    // 5. Test the web route using HTTP simulation
    echo "\n4. Testing web route via HTTP simulation...\n";
    
    // Create a mock request
    $request = Request::create('/api/financial-transactions-bulk-payment', 'POST', [
        'fiche_navette_id' => $ficheNavette->id,
        'patient_id' => $patient->id,
        'payment_method' => 'bank_transfer',
        'total_amount' => 1000.00,
        'is_bank_transaction' => true,
        'bank_id' => $bank->id,
        'items' => [
            [
                'fiche_navette_item_id' => $ficheNavetteItem->id,
                'amount' => 1000.00
            ]
        ]
    ]);

    // Set content type
    $request->headers->set('Content-Type', 'application/json');
    $request->headers->set('Accept', 'application/json');

    echo "   Request data: " . json_encode($request->all(), JSON_PRETTY_PRINT) . "\n";

    // Test controller directly
    $controller = new \App\Http\Controllers\Caisse\FinancialTransactionController();
    
    try {
        // Create a proper request instance
        $formRequest = new \App\Http\Requests\Caisse\BulkPaymentRequest();
        $formRequest->replace($request->all());
        $formRequest->setContainer(app());
        
        $response = $controller->bulkPayment($formRequest);
        echo "   Controller response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
    } catch (\Exception $e) {
        echo "   Controller error: " . $e->getMessage() . "\n";
        echo "   Stack trace: " . $e->getTraceAsString() . "\n";
    }

    // 6. Check database state after all tests
    echo "\n5. Final database state check...\n";
    
    $allTransactions = \App\Models\Caisse\FinancialTransaction::where('fiche_navette_id', $ficheNavette->id)->get();
    
    echo "   Total transactions found: " . $allTransactions->count() . "\n";
    
    foreach ($allTransactions as $index => $trans) {
        echo "   Transaction " . ($index + 1) . ":\n";
        echo "     ID: {$trans->id}\n";
        echo "     Payment Method: {$trans->payment_method}\n";
        echo "     Is Bank Transaction: " . ($trans->is_bank_transaction ? 'true' : 'false') . "\n";
        echo "     Bank ID: " . ($trans->bank_id ?? 'null') . "\n";
        echo "     Amount: {$trans->amount}\n";
        echo "     Created: {$trans->created_at}\n";
    }

    // 7. Clean up test data
    echo "\n6. Cleaning up test data...\n";
    
    \App\Models\Caisse\FinancialTransaction::where('fiche_navette_id', $ficheNavette->id)->delete();
    $ficheNavetteItem->delete();
    $ficheNavette->delete();
    $patient->delete();
    
    echo "   Test data cleaned up.\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";