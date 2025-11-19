<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Coffre\Caisse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Simulate an authenticated user session
$user = \App\Models\User::first();
if ($user) {
    Auth::loginUsingId($user->id, true);
    echo "Authenticated as: {$user->name}\n";
} else {
    echo "ERROR: No users in database. Cannot authenticate.\n";
    exit(1);
}

// Test the complete delete flow
echo "\n=== Complete Delete Flow Test ===\n\n";

// Step 1: Create a test caisse
echo "Step 1: Create test caisse\n";
$testCaisse = Caisse::create([
    'name' => 'Delete Test Caisse ' . now()->timestamp,
    'location' => 'Delete Test Location',
    'is_active' => true,
    'service_id' => 1
]);
echo "  Created caisse ID: {$testCaisse->id}, Name: {$testCaisse->name}\n";

// Step 2: Verify it exists
echo "\nStep 2: Verify caisse exists\n";
$check1 = Caisse::find($testCaisse->id);
echo "  Found in DB: " . ($check1 ? "YES" : "NO") . "\n";

// Step 3: Simulate the API delete request
echo "\nStep 3: Simulate API DELETE request\n";

try {
    $controller = new \App\Http\Controllers\Coffre\CaisseController(
        new \App\Services\Coffre\CaisseService()
    );
    
    // Create a mock request
    $mockRequest = new Request();
    $mockRequest->setMethod('DELETE');
    
    // Call destroy
    $response = $controller->destroy($testCaisse);
    
    echo "  Response status: " . $response->getStatusCode() . "\n";
    echo "  Response body: " . $response->getContent() . "\n";
    
} catch (\Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}

// Step 4: Verify deletion
echo "\nStep 4: Verify deletion in database\n";
$check2 = Caisse::find($testCaisse->id);
echo "  Still in DB: " . ($check2 ? "YES (PROBLEM!)" : "NO (Good)") . "\n";

// Step 5: Try to fetch using getAll (like the frontend does)
echo "\nStep 5: Test getAll() method (frontend refresh)\n";
$service = new \App\Services\Coffre\CaisseService();
$result = $service->getAllPaginated([], 15);

echo "  Total caisses after delete: " . $result->total() . "\n";
foreach ($result->items() as $caisse) {
    echo "    - ID: {$caisse->id}, Name: {$caisse->name}\n";
}

// Step 6: Check cache
echo "\nStep 6: Check if cache is interfering\n";
$cached = \Illuminate\Support\Facades\Cache::get('caisses_all');
echo "  Cached 'caisses_all': " . ($cached ? "YES - " . count($cached) . " items" : "NO") . "\n";

$totalInDB = Caisse::count();
echo "\nFinal: Total caisses in database: $totalInDB\n";

echo "\n=== Test Complete ===\n";

?>
