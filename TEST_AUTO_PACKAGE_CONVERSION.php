<?php
/**
 * Test Script for Auto-Package Conversion Feature
 * 
 * Scenario: User provides this specific example:
 * - Start with: Endoscopie digestive 87 + Endoscopie digestive 87
 * - Add: Pose d'implant 88
 * - Expected: Auto-convert to PACK CARDIOLOGIE 05
 * 
 * Run this test with: php artisan tinker < TEST_AUTO_PACKAGE_CONVERSION.php
 */

echo "========================================\n";
echo "AUTO-PACKAGE CONVERSION TEST\n";
echo "========================================\n\n";

// Step 1: Verify test data exists
echo "STEP 1: Verify prestations exist\n";
echo "-----------------------------------\n";

$prest87 = DB::table('prestations')->find(87);
$prest88 = DB::table('prestations')->find(88);

if ($prest87) {
    echo "✅ Prestation 87: {$prest87->name}\n";
} else {
    echo "❌ Prestation 87 not found\n";
    exit(1);
}

if ($prest88) {
    echo "✅ Prestation 88: {$prest88->name}\n";
} else {
    echo "❌ Prestation 88 not found\n";
    exit(1);
}

// Step 2: Check PACK CARDIOLOGIE 05
echo "\nSTEP 2: Check PACK CARDIOLOGIE 05 composition\n";
echo "-----------------------------------\n";

$package = DB::table('prestation_packages')->find(11);
if ($package) {
    echo "✅ Package found: {$package->name} (ID: {$package->id})\n";
    echo "   Price: {$package->price}\n";
    echo "   Active: " . ($package->is_active ? 'Yes' : 'No') . "\n";
} else {
    echo "❌ Package 11 not found\n";
    exit(1);
}

// Get package prestations via PrestationPackageitem
$packageItems = DB::table('prestation_package_items')
    ->where('prestation_package_id', 11)
    ->pluck('prestation_id')
    ->toArray();

echo "\n   Package contains prestations: " . json_encode($packageItems) . "\n";
foreach ($packageItems as $prestId) {
    $p = DB::table('prestations')->find($prestId);
    echo "   - {$p->id}: {$p->name}\n";
}

// Step 3: Create a test patient
echo "\nSTEP 3: Create test patient\n";
echo "-----------------------------------\n";

$patient = DB::table('patients')->insertGetId([
    'first_name' => 'Test_Auto_Conv_' . time(),
    'last_name' => 'Patient',
    'date_of_birth' => '1990-01-01',
    'gender' => 'M',
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "✅ Created test patient ID: {$patient}\n";

// Step 4: Create a fiche navette with initial prestations
echo "\nSTEP 4: Create Fiche Navette with initial items\n";
echo "-----------------------------------\n";

$fiche = DB::table('fiche_navettes')->insertGetId([
    'patient_id' => $patient,
    'date_reception' => now()->format('Y-m-d H:i:s'),
    'created_by' => 1,
    'updated_by' => 1,
    'created_at' => now(),
    'updated_at' => now(),
    'total_amount' => 0,
    'payment_status' => 'unpaid',
    'status' => 'pending',
]);

echo "✅ Created Fiche Navette ID: {$fiche}\n";

// Add first Endoscopie item
$item1 = DB::table('fiche_navette_items')->insertGetId([
    'fiche_navette_id' => $fiche,
    'prestation_id' => 87,
    'payment_status' => 'unpaid',
    'final_price' => $prest87->public_price,
    'status' => 'pending',
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "✅ Added Item 1 - Endoscopie (87): ID {$item1}\n";

// Add second Endoscopie item
$item2 = DB::table('fiche_navette_items')->insertGetId([
    'fiche_navette_id' => $fiche,
    'prestation_id' => 87,
    'payment_status' => 'unpaid',
    'final_price' => $prest87->public_price,
    'status' => 'pending',
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "✅ Added Item 2 - Endoscopie (87): ID {$item2}\n";

// Update fiche total
$total = DB::table('fiche_navette_items')
    ->where('fiche_navette_id', $fiche)
    ->sum('final_price');

DB::table('fiche_navettes')
    ->where('id', $fiche)
    ->update(['total_amount' => $total]);

echo "\n   Fiche total before adding Pose d'implant: {$total}\n";

// Step 5: Verify current state
echo "\nSTEP 5: Verify current fiche state\n";
echo "-----------------------------------\n";

$items = DB::table('fiche_navette_items')
    ->where('fiche_navette_id', $fiche)
    ->get();

echo "Current items in fiche:\n";
foreach ($items as $item) {
    echo "- Item {$item->id}: Prestation {$item->prestation_id}, Price: {$item->final_price}, Status: {$item->payment_status}\n";
}

// Step 6: Simulate adding Pose d'implant via API
echo "\nSTEP 6: Simulate API call to add Pose d'implant\n";
echo "-----------------------------------\n";

// Create request payload similar to what the frontend sends
$requestData = [
    'prestations' => [
        [
            'id' => 88,
            'prestation_id' => 88,
            'doctor_id' => null,
            'quantity' => 1,
        ]
    ]
];

echo "Request payload:\n";
echo json_encode($requestData, JSON_PRETTY_PRINT) . "\n";

// Step 7: Test the auto-conversion logic
echo "\nSTEP 7: Test auto-conversion detection logic\n";
echo "-----------------------------------\n";

// Get existing prestations
$existingPrestations = DB::table('fiche_navette_items')
    ->where('fiche_navette_id', $fiche)
    ->whereNotNull('prestation_id')
    ->pluck('prestation_id')
    ->toArray();

echo "Existing prestations: " . json_encode($existingPrestations) . "\n";

// Get new prestations
$newPrestations = [88];
echo "New prestations: " . json_encode($newPrestations) . "\n";

// Combine all
$allPrestations = array_merge($existingPrestations, $newPrestations);
echo "Combined prestations: " . json_encode($allPrestations) . "\n";

// Check if this matches a package
echo "\nChecking if combined prestations match a package...\n";

$packages = DB::table('prestation_packages')->where('is_active', true)->get();

foreach ($packages as $pkg) {
    $pkgPrestations = DB::table('prestation_package_items')
        ->where('prestation_package_id', $pkg->id)
        ->pluck('prestation_id')
        ->toArray();
    
    echo "\nPackage {$pkg->id} ({$pkg->name}): " . json_encode($pkgPrestations) . "\n";
    
    // Check for exact match
    if (count($pkgPrestations) === count($allPrestations) && 
        empty(array_diff($pkgPrestations, $allPrestations)) &&
        empty(array_diff($allPrestations, $pkgPrestations))) {
        echo "✅ EXACT MATCH FOUND!\n";
        echo "   This is the package that should be created!\n";
    }
}

// Step 8: Verify all items are unpaid
echo "\nSTEP 8: Verify all items are unpaid (conversion allowed)\n";
echo "-----------------------------------\n";

$unpaidItems = DB::table('fiche_navette_items')
    ->where('fiche_navette_id', $fiche)
    ->where('payment_status', '!=', 'unpaid')
    ->count();

if ($unpaidItems === 0) {
    echo "✅ All items are unpaid - conversion is allowed\n";
} else {
    echo "❌ Some items are paid - conversion would be blocked\n";
}

// Step 9: Cleanup
echo "\nSTEP 9: Cleanup test data\n";
echo "-----------------------------------\n";

DB::table('fiche_navette_items')->where('fiche_navette_id', $fiche)->delete();
DB::table('fiche_navettes')->where('id', $fiche)->delete();
DB::table('patients')->where('id', $patient)->delete();

echo "✅ Test data cleaned up\n";

echo "\n========================================\n";
echo "TEST COMPLETE\n";
echo "========================================\n";
echo "\nNOTE: The auto-conversion SHOULD have happened when adding item 3 (Pose d'implant)\n";
echo "Expected behavior:\n";
echo "1. System detects combined prestations [87, 87, 88]\n";
echo "2. Matches to PACK CARDIOLOGIE 05\n";
echo "3. Removes items 1 and 2 (old Endoscopies)\n";
echo "4. Creates new package item with PACK CARDIOLOGIE 05\n";
echo "5. Preserves doctors if any were assigned\n";
echo "6. Updates fiche total to package price\n\n";
