<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Waitlist API ===\n\n";

// Test 1: Fetch all daily waitlists
echo "Test 1: Fetching daily waitlists (is_Daily=1)\n";
$waitlists = \App\Models\WaitList::with(['doctor.user', 'patient', 'specialization'])
    ->where('is_Daily', 1)
    ->get();

echo "Found {$waitlists->count()} daily waitlists\n";
if ($waitlists->count() > 0) {
    $first = $waitlists->first();
    echo "Sample waitlist data:\n";
    echo "  - ID: {$first->id}\n";
    echo "  - Doctor: " . ($first->doctor->user->name ?? 'N/A') . "\n";
    echo "  - Patient: " . ($first->patient->Firstname ?? 'N/A') . " " . ($first->patient->Lastname ?? 'N/A') . "\n";
    echo "  - Phone: " . ($first->patient->phone ?? 'N/A') . "\n";
    echo "  - Specialization: " . ($first->specialization->name ?? 'N/A') . "\n";
    echo "  - Importance: " . $first->importance . "\n";
    echo "  - Notes: " . ($first->notes ?? 'N/A') . "\n\n";
}

// Test 2: Test resource transformation
echo "Test 2: Testing WaitListResource transformation\n";
if ($waitlists->count() > 0) {
    $resource = new \App\Http\Resources\WaitListResource($waitlists->first());
    $resourceArray = $resource->toArray(request());
    echo "Resource fields:\n";
    foreach ($resourceArray as $key => $value) {
        if (!is_array($value) && !is_object($value)) {
            echo "  - $key: $value\n";
        }
    }
    echo "\n";
}

// Test 3: Simulate API call with filters
echo "Test 3: Testing API filters\n";
$query = \App\Models\WaitList::with(['doctor.user', 'patient', 'specialization']);
$query->where('is_Daily', 1);

echo "Filter by importance=1 (Urgent):\n";
$urgent = clone $query;
$urgent->where('importance', 1);
echo "  Found " . $urgent->count() . " urgent waitlists\n";

echo "Filter by doctor_id=null (No doctor assigned):\n";
$nodoctor = clone $query;
$nodoctor->whereNull('doctor_id');
echo "  Found " . $nodoctor->count() . " waitlists without doctor\n\n";

// Test 4: Check importance enum endpoint
echo "Test 4: Checking importance enum\n";
$importanceEnum = \App\ImportanceEnum::cases();
echo "Importance enum values:\n";
foreach ($importanceEnum as $case) {
    echo "  - Name: {$case->name}, Value: {$case->value}\n";
}

echo "\n=== All tests completed ===\n";
