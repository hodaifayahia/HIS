<?php

// Quick test of checkAndPreparePackageConversion fix

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$fiche_id = 4; // Use an existing fiche

echo "\n=== TESTING AUTO-PACKAGE CONVERSION FIX ===\n\n";

// Test data
$existingPrestationIds = [87, 87]; // Two Endoscopie items
$newPrestationIds = [88];           // Adding Pose d'implant

echo 'Existing prestations: '.json_encode($existingPrestationIds)."\n";
echo 'New prestations: '.json_encode($newPrestationIds)."\n";

// Show what the OLD code would do (with array_unique)
$allWithUnique = array_unique(array_merge($existingPrestationIds, $newPrestationIds));
echo "\nOLD CODE (with array_unique): ".json_encode($allWithUnique)."\n";
echo "^ This is WRONG because it removes the duplicate 87!\n";

// Show what the NEW code does (without array_unique)
$allWithoutUnique = array_merge($existingPrestationIds, $newPrestationIds);
echo "\nNEW CODE (without array_unique): ".json_encode($allWithoutUnique)."\n";
echo "^ This is CORRECT because it keeps both 87 values!\n";

// Now test the actual service method
echo "\n=== TESTING ACTUAL SERVICE METHOD ===\n\n";

$service = app(\App\Services\Reception\ReceptionService::class);
$result = $service->checkAndPreparePackageConversion($fiche_id, $newPrestationIds, $existingPrestationIds);

echo "Result:\n";
echo '- Should Convert: '.($result['should_convert'] ? '✅ YES' : '❌ NO')."\n";
echo '- Package ID: '.($result['package_id'] ?? 'null')."\n";
echo '- Package Name: '.($result['package_name'] ?? 'null')."\n";
echo '- Message: '.($result['message'] ?? 'null')."\n";

if ($result['should_convert']) {
    echo "\n✅ SUCCESS! Auto-conversion will be triggered!\n";
} else {
    echo "\n❌ Auto-conversion will NOT be triggered.\n";
    echo "\nDEBUGGING:\n";
    echo "- Check database logs for details\n";
}

echo "\n";
