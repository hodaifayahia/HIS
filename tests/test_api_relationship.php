<?php

require_once __DIR__.'/vendor/autoload.php';

use App\Http\Controllers\Caisse\FinancialTransactionController;
use App\Services\Caisse\FinancialTransactionService;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing FinancialTransaction API endpoint relationship loading...\n\n";

try {
    // Create service and controller instances
    $service = new FinancialTransactionService;
    $controller = new FinancialTransactionController($service);

    // Create a mock request
    $request = new Request;
    $request->merge([
        'per_page' => 5,
        'page' => 1,
    ]);

    // Call the index method
    $response = $controller->index($request);

    // Check if response is successful
    if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getContent(), true);

        if (isset($data['data']) && is_array($data['data']) && count($data['data']) > 0) {
            echo '✅ API call successful. Found '.count($data['data'])." transactions.\n\n";

            // Check all transactions for relationship data
            foreach ($data['data'] as $index => $transaction) {
                echo 'Transaction '.($index + 1)." (ID: {$transaction['id']}):\n";

                if (array_key_exists('fiche_navette_item', $transaction)) {
                    if ($transaction['fiche_navette_item'] !== null) {
                        echo "  ✅ fiche_navette_item relationship loaded with data!\n";
                        echo '     - Item ID: '.($transaction['fiche_navette_item']['id'] ?? 'N/A')."\n";
                        echo '     - Status: '.($transaction['fiche_navette_item']['status'] ?? 'N/A')."\n";
                    } else {
                        echo "  ⚠️  fiche_navette_item relationship is null\n";
                    }
                } else {
                    echo "  ❌ fiche_navette_item key is missing!\n";
                }

                if (array_key_exists('fiche_navette_item_status', $transaction)) {
                    echo "  ✅ fiche_navette_item_status: {$transaction['fiche_navette_item_status']}\n";
                } else {
                    echo "  ❌ fiche_navette_item_status key is missing!\n";
                }
                echo "\n";
            }

        } else {
            echo "⚠️  No transaction data found in response\n";
        }

    } else {
        echo '❌ API call failed with status code: '.$response->getStatusCode()."\n";
        echo 'Response: '.$response->getContent()."\n";
    }

    // Test 4: Check if there are any transactions with fiche_navette_item_id
    echo "Test 4: Checking for transactions with fiche_navette_item_id...\n";
    $transactionsWithItems = \App\Models\Caisse\FinancialTransaction::whereNotNull('fiche_navette_item_id')->take(3)->get();

    if ($transactionsWithItems->count() > 0) {
        echo "✅ Found {$transactionsWithItems->count()} transactions with fiche_navette_item_id\n";

        foreach ($transactionsWithItems as $txn) {
            echo "  Transaction ID {$txn->id}: fiche_navette_item_id = {$txn->fiche_navette_item_id}\n";
        }

        // Test one of these transactions
        $testTransaction = $transactionsWithItems->first();
        $testTransaction->load('ficheNavetteItem');

        if ($testTransaction->ficheNavetteItem) {
            echo "✅ Relationship loaded successfully for transaction {$testTransaction->id}\n";
            echo "   - Item ID: {$testTransaction->ficheNavetteItem->id}\n";
            echo "   - Status: {$testTransaction->ficheNavetteItem->status}\n";
        } else {
            echo "❌ Relationship failed to load for transaction {$testTransaction->id}\n";
        }
    } else {
        echo "⚠️  No transactions found with fiche_navette_item_id\n";
    }

    echo "\n";

} catch (Exception $e) {
    echo '❌ Error during test: '.$e->getMessage()."\n";
    echo "Stack trace:\n".$e->getTraceAsString()."\n";
}

echo "\nAPI Test completed.\n";
