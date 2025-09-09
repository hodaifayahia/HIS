<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Caisse\FinancialTransaction;
use App\Services\Caisse\FinancialTransactionService;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing FinancialTransaction ficheNavetteItem relationship loading...\n\n";

try {
    // Test 1: Direct model relationship loading
    echo "Test 1: Direct model relationship loading\n";
    $transaction = FinancialTransaction::with('ficheNavetteItem')->first();

    if ($transaction) {
        echo "✅ Found transaction ID: {$transaction->id}\n";

        if ($transaction->ficheNavetteItem) {
            echo "✅ ficheNavetteItem relationship loaded successfully!\n";
            echo "   - Item ID: {$transaction->ficheNavetteItem->id}\n";
            echo "   - Status: {$transaction->ficheNavetteItem->status}\n";
        } else {
            echo "⚠️  ficheNavetteItem relationship is null (transaction may not have an associated item)\n";
        }
    } else {
        echo "❌ No transactions found in database\n";
    }

    echo "\n";

    // Test 2: Service method relationship loading
    echo "Test 2: Service method relationship loading\n";
    $service = new FinancialTransactionService();

    $paginated = $service->getAllPaginated([], 5);
    $items = $paginated->items();

    if (count($items) > 0) {
        echo "✅ Service returned " . count($items) . " transactions\n";

        $firstTransaction = $items[0];
        echo "Checking first transaction (ID: {$firstTransaction->id})...\n";

        // Manually load relationships
        $firstTransaction->load('ficheNavetteItem');

        if ($firstTransaction->ficheNavetteItem) {
            echo "✅ ficheNavetteItem relationship loaded successfully!\n";
            echo "   - Item ID: {$firstTransaction->ficheNavetteItem->id}\n";
            echo "   - Status: {$firstTransaction->ficheNavetteItem->status}\n";
        } else {
            echo "⚠️  ficheNavetteItem relationship is null\n";
        }
    } else {
        echo "❌ Service returned no transactions\n";
    }

    echo "\n";

    // Test 3: Check if transactions have fiche_navette_item_id
    echo "Test 3: Check transaction fiche_navette_item_id values\n";
    $transactions = FinancialTransaction::take(5)->get();

    foreach ($transactions as $txn) {
        echo "Transaction ID {$txn->id}: fiche_navette_item_id = " . ($txn->fiche_navette_item_id ?? 'NULL') . "\n";
    }

} catch (Exception $e) {
    echo "❌ Error during test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest completed.\n";
