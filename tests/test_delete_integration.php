<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Coffre\Caisse;
use App\Models\User;

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║        DELETE FUNCTIONALITY - COMPLETE INTEGRATION TEST        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Setup
Caisse::query()->delete();
$service = new \App\Services\Coffre\CaisseService;

echo "📋 TEST SCENARIO: User deletes a cash register\n";
echo "─────────────────────────────────────────────────────────────────\n\n";

// Step 1: Create test data
echo "STEP 1️⃣  : Create test cash registers\n";
$testData = [];
for ($i = 1; $i <= 3; $i++) {
    $caisse = Caisse::create([
        'name' => 'Test Caisse '.$i,
        'location' => 'Test Location '.$i,
        'is_active' => $i % 2 === 0,
        'service_id' => 1,
    ]);
    $testData[] = $caisse;
    echo "  ✓ Created: ID={$caisse->id}, Name='{$caisse->name}'\n";
}
echo "\n";

// Step 2: Frontend calls getAll() for initial list
echo "STEP 2️⃣  : Frontend loads initial list (getAll)\n";
$initialList = $service->getAllPaginated([], 15);
echo '  ✓ API Returns: '.$initialList->total()." caisses\n";
$initialItems = $initialList->items();
echo "  ✓ Items in list:\n";
foreach ($initialItems as $item) {
    echo "      - ID={$item->id}, Name='{$item->name}'\n";
}
echo "\n";

// Step 3: User clicks delete and confirms
$toDelete = $initialItems[0];
echo "STEP 3️⃣  : User clicks Delete on caisse (ID={$toDelete->id})\n";
echo "  ✓ Confirmation dialog shown\n";
echo "  ✓ User confirms deletion\n";
echo "\n";

// Step 4: Frontend calls API delete
echo "STEP 4️⃣  : Frontend sends DELETE /api/caisses/{$toDelete->id}\n";

// Simulate the controller
$controller = new \App\Http\Controllers\Coffre\CaisseController($service);
$caisse = Caisse::findOrFail($toDelete->id);

// Call destroy
$response = $controller->destroy($caisse);
$responseBody = json_decode($response->getContent(), true);

echo '  ✓ Response Status: '.$response->getStatusCode()."\n";
echo '  ✓ Response Body: '.json_encode($responseBody, JSON_PRETTY_PRINT)."\n";
echo "\n";

// Verify deletion
echo "STEP 5️⃣  : Verify item is deleted from database\n";
$stillExists = Caisse::find($toDelete->id);
if ($stillExists) {
    echo "  ✗ ERROR: Item still in database!\n";
} else {
    echo "  ✓ Item successfully deleted from database\n";
}
echo "\n";

// Step 6: Frontend calls getAll() again to refresh list
echo "STEP 6️⃣  : Frontend refreshes list (await fetchCaisses)\n";
$refreshedList = $service->getAllPaginated([], 15);
echo '  ✓ API Returns: '.$refreshedList->total().' caisses (was '.count($initialItems).")\n";
$refreshedItems = $refreshedList->items();
echo "  ✓ Items in refreshed list:\n";
foreach ($refreshedItems as $item) {
    echo "      - ID={$item->id}, Name='{$item->name}'\n";
}
echo "\n";

// Step 7: Verify deleted item is not in list
echo "STEP 7️⃣  : Verify deleted item not in refreshed list\n";
$deletedStillVisible = collect($refreshedItems)->contains(function ($item) use ($toDelete) {
    return $item->id === $toDelete->id;
});

if ($deletedStillVisible) {
    echo "  ✗ ERROR: Deleted item still visible in list!\n";
} else {
    echo "  ✓ Deleted item not in refreshed list\n";
}
echo "\n";

// Final Summary
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                      TEST SUMMARY                              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$dbCount = Caisse::count();
$expectedCount = count($initialItems) - 1;
$allTestsPassed = ! $stillExists && ! $deletedStillVisible && $dbCount === $expectedCount;

echo "Database checks:\n";
echo '  • Initial count: '.count($initialItems)."\n";
echo '  • Expected after delete: '.$expectedCount."\n";
echo '  • Actual after delete: '.$dbCount."\n";
echo "\n";

echo "Deletion verification:\n";
echo '  • Item deleted from DB: '.($stillExists ? '❌ NO' : '✅ YES')."\n";
echo '  • Item removed from list: '.($deletedStillVisible ? '❌ NO' : '✅ YES')."\n";
echo '  • API response successful: '.($responseBody['success'] ? '✅ YES' : '❌ NO')."\n";
echo "\n";

if ($allTestsPassed) {
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ ALL TESTS PASSED - DELETE FUNCTIONALITY IS WORKING        ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
} else {
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ❌ TESTS FAILED - DELETE FUNCTIONALITY HAS ISSUES            ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
}
echo "\n";
