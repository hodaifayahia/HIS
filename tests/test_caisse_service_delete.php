<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Coffre\Caisse;

// Test the API delete functionality via HTTP
echo "=== Caisse API Delete Test ===\n\n";

// First, let's create a test caisse if needed
$caisses = Caisse::all();
echo 'Current caisses in database: '.count($caisses)."\n";

if (count($caisses) === 0) {
    echo "Creating test caisse...\n";
    $testCaisse = Caisse::create([
        'name' => 'API Test Caisse '.now()->timestamp,
        'location' => 'API Test Location',
        'is_active' => true,
        'service_id' => 1,
    ]);
    echo "Created caisse with ID: {$testCaisse->id}\n";
    $caisseId = $testCaisse->id;
} else {
    $caisseId = $caisses->first()->id;
    echo "Using existing caisse ID: $caisseId\n";
}

// List all caisses before
echo "\n--- Before Delete ---\n";
$before = Caisse::all();
echo 'Total caisses: '.count($before)."\n";
foreach ($before as $caisse) {
    echo "  - ID: {$caisse->id}, Name: {$caisse->name}\n";
}

// Now simulate what the controller does
echo "\n--- Simulating API Delete Request ---\n";

try {
    // Load the caisse
    $caisse = Caisse::findOrFail($caisseId);
    echo "Found caisse: {$caisse->name}\n";

    // Call the service
    $service = new \App\Services\Coffre\CaisseService;
    echo "Calling service->delete()...\n";
    $service->delete($caisse);
    echo "Service delete completed.\n";

    // Check result
    $stillExists = Caisse::where('id', $caisseId)->exists();
    echo 'Caisse still in database: '.($stillExists ? 'YES (PROBLEM!)' : 'NO (Good)')."\n";

} catch (\Exception $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
}

// List all caisses after
echo "\n--- After Delete ---\n";
$after = Caisse::all();
echo 'Total caisses: '.count($after)."\n";
foreach ($after as $caisse) {
    echo "  - ID: {$caisse->id}, Name: {$caisse->name}\n";
}

echo "\n--- Result Summary ---\n";
echo 'Deleted: '.(count($before) - count($after))." caisse(s)\n";

if (count($before) > count($after)) {
    echo "✓ DELETE WORKING CORRECTLY\n";
} else {
    echo "✗ DELETE NOT WORKING - DATA NOT BEING REMOVED\n";
}
