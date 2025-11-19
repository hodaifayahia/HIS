<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Bank Transfer API Endpoint\n";
echo "==================================\n";

// First, let's create a user and authenticate
$user = App\Models\User::first();
if (! $user) {
    echo "No users found. Creating test user...\n";
    $user = App\Models\User::create([
        'name' => 'Test Cashier',
        'email' => 'cashier@test.com',
        'password' => bcrypt('password'),
        'role' => 'cashier',
    ]);
}

echo "Using user: {$user->name} (ID: {$user->id})\n";

// Test the service directly with bank transfer data
echo "\nTesting FinancialTransactionService with bank transfer...\n";

$service = new App\Services\Caisse\FinancialTransactionService;

// Get test data
$patient = App\Models\Patient::first();
$fiche = App\Models\Reception\ficheNavette::first();
$item = App\Models\Reception\ficheNavetteItem::first();

if (! $patient || ! $fiche || ! $item) {
    echo "Missing test data. Creating...\n";
    // Create minimal test data if needed
    $patient = App\Models\Patient::create([
        'Firstname' => 'Test',
        'Lastname' => 'Patient',
        'dateOfBirth' => '1990-01-01',
        'gender' => 'male',
        'phone' => '123456789',
    ]);

    $fiche = App\Models\Reception\ficheNavette::create([
        'patient_id' => $patient->id,
        'fiche_date' => now(),
        'status' => 'active',
    ]);

    $item = App\Models\Reception\ficheNavetteItem::create([
        'fiche_navette_id' => $fiche->id,
        'prestation_id' => 1,
        'base_price' => 100.00,
        'final_price' => 100.00,
        'paid_amount' => 0,
        'remaining_amount' => 100.00,
    ]);
}

echo "Test data - Patient ID: {$patient->id}, Fiche ID: {$fiche->id}, Item ID: {$item->id}\n";

// Test payload with bank transfer
$payload = [
    'fiche_navette_id' => $fiche->id,
    'cashier_id' => $user->id,
    'patient_id' => $patient->id,
    'payment_method' => 'bank_transfer',
    'transaction_type' => 'payment',
    'total_amount' => 50.00,
    'is_bank_transaction' => true,
    'bank_id' => 11, // Test Bank 1
    'items' => [
        [
            'fiche_navette_item_id' => $item->id,
            'amount' => 50.00,
        ],
    ],
];

echo "\nPayload:\n";
echo json_encode($payload, JSON_PRETTY_PRINT)."\n";

try {
    $result = $service->createBulkPayments($payload);
    echo "\nSUCCESS: Bulk payment created\n";

    // Check the latest transaction
    $latest = App\Models\Caisse\FinancialTransaction::orderBy('id', 'desc')->first();
    echo "Latest transaction:\n";
    echo "- ID: {$latest->id}\n";
    echo "- Payment Method: {$latest->payment_method}\n";
    echo '- Is Bank Transaction: '.($latest->is_bank_transaction ? 'YES' : 'NO')."\n";
    echo '- Bank ID: '.($latest->bank_id ?: 'NULL')."\n";
    echo "- Amount: {$latest->amount}\n";

} catch (Exception $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
    echo 'Stack trace: '.$e->getTraceAsString()."\n";
}
