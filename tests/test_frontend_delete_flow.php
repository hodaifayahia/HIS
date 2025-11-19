<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Coffre\Caisse;

echo "=== Frontend-Like Delete Simulation ===\n\n";

// 1. Create 5 test caisses
echo "Step 1: Create test data\n";
Caisse::query()->delete(); // Clean slate
$count = 5;
for ($i = 1; $i <= $count; $i++) {
    Caisse::create([
        'name' => 'Caisse '.$i,
        'location' => 'Location '.$i,
        'is_active' => true,
        'service_id' => 1,
    ]);
}
echo "  Created $count caisses\n";

// 2. Get initial list (frontend: getAll())
echo "\nStep 2: Initial list (frontend calls getAll with pagination)\n";
$service = new \App\Services\Coffre\CaisseService;
$initialResult = $service->getAllPaginated([], 15);
echo '  Total: '.$initialResult->total()."\n";
$initialIds = array_map(fn ($c) => $c->id, $initialResult->items());
echo '  IDs: '.implode(', ', $initialIds)."\n";

// 3. Delete the first one
echo "\nStep 3: Delete first caisse (ID=".$initialIds[0].")\n";
$toDelete = Caisse::find($initialIds[0]);
echo '  Before delete - exists in DB: '.(Caisse::find($toDelete->id) ? 'YES' : 'NO')."\n";

$service->delete($toDelete);

echo '  After delete - exists in DB: '.(Caisse::find($toDelete->id) ? 'YES (PROBLEM!)' : 'NO')."\n";

// 4. Fetch list again (frontend: await fetchCaisses())
echo "\nStep 4: Refresh list after delete (frontend calls getAll again)\n";
$refreshedResult = $service->getAllPaginated([], 15);
echo '  Total: '.$refreshedResult->total()."\n";
$refreshedIds = array_map(fn ($c) => $c->id, $refreshedResult->items());
echo '  IDs: '.implode(', ', $refreshedIds)."\n";

// 5. Verify deleted ID is gone
echo "\nStep 5: Verify deletion\n";
$deletedInList = in_array($toDelete->id, $refreshedIds);
echo '  Deleted ID in new list: '.($deletedInList ? 'YES (PROBLEM!)' : 'NO (Good)')."\n";

// 6. Compare counts
echo "\nStep 6: Summary\n";
echo '  Initial count: '.$initialResult->total()."\n";
echo '  Refreshed count: '.$refreshedResult->total()."\n";
echo '  Difference: '.($initialResult->total() - $refreshedResult->total())."\n";

if ($initialResult->total() - $refreshedResult->total() === 1 && ! $deletedInList) {
    echo "\n✓ DELETE WORKING: Item deleted and not in refreshed list\n";
} else {
    echo "\n✗ DELETE PROBLEM: Item might still be visible in UI\n";
}
