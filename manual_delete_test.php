<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Coffre\Caisse;
use App\Models\User;

// First, create test data
echo "=== Manual Delete Test (Simulating Full Flow) ===\n\n";

// Create test caisses
echo "Creating test caisses...\n";
$testCaisses = [];
for ($i = 1; $i <= 3; $i++) {
    $caisse = Caisse::create([
        'name' => 'Delete Test Caisse ' . $i,
        'location' => 'Location ' . $i,
        'is_active' => true,
        'service_id' => 1
    ]);
    $testCaisses[] = $caisse;
    echo "  Created: ID={$caisse->id}, Name={$caisse->name}\n";
}

// List all before
echo "\nCaisses before delete:\n";
$all = Caisse::all();
echo "  Total: " . count($all) . "\n";
foreach ($all as $c) {
    echo "    - ID={$c->id}, Name={$c->name}\n";
}

// Delete the first one
echo "\nDeleting caisse ID=" . $testCaisses[0]->id . "\n";
$caisse_to_delete = $testCaisses[0];

// Step through exactly what the controller does
$service = new \App\Services\Coffre\CaisseService();

echo "  Before service->delete(): DB count = " . Caisse::count() . "\n";
$service->delete($caisse_to_delete);
echo "  After service->delete(): DB count = " . Caisse::count() . "\n";

// Check if it's really gone
$shouldBeGone = Caisse::find($caisse_to_delete->id);
echo "  Caisse still in DB: " . ($shouldBeGone ? "YES (PROBLEM!)" : "NO (Good)") . "\n";

// Get all again using the service method (like frontend does)
echo "\nFetching caisses using service->getAllPaginated():\n";
$paginated = $service->getAllPaginated([], 15);
echo "  Total returned: " . $paginated->total() . "\n";
foreach ($paginated->items() as $c) {
    echo "    - ID={$c->id}, Name={$c->name}\n";
}

echo "\n=== Test Complete ===\n";
echo "If all counts match and deleted caisse is gone, DELETE IS WORKING\n";

?>
