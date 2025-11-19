#!/bin/bash
# Test Script for Auto-Package Conversion Feature
# Test the exact scenario: Add Endoscopie (87) + Endoscopie (87) + Pose d'implant (88) = PACK CARDIOLOGIE 05

echo "================================================"
echo "AUTO-PACKAGE CONVERSION - END-TO-END TEST"
echo "================================================"
echo ""

# Step 1: Verify data exists
echo "STEP 1: Verifying test data..."
php artisan tinker << 'EOF'
echo "\n=== PRESTATIONS ===\n";
$p87 = \DB::table('prestations')->find(87);
$p88 = \DB::table('prestations')->find(88);
echo "87: " . ($p87 ? $p87->name : "❌ NOT FOUND") . "\n";
echo "88: " . ($p88 ? $p88->name : "❌ NOT FOUND") . "\n";

echo "\n=== PACKAGE 11 (PACK CARDIOLOGIE 05) ===\n";
$pkg = \DB::table('prestation_packages')->find(11);
if ($pkg) {
    echo "Name: " . $pkg->name . "\n";
    echo "Price: " . $pkg->price . "\n";
    echo "Active: " . ($pkg->is_active ? "✅ YES" : "❌ NO") . "\n";
} else {
    echo "❌ Package 11 not found\n";
}

echo "\n=== PACKAGE 11 CONTENTS ===\n";
$items = \DB::table('prestation_package_items')
    ->where('prestation_package_id', 11)
    ->pluck('prestation_id')
    ->toArray();
echo "Prestations in package: " . json_encode($items) . "\n";
echo "Expected: [87, 87, 88]\n";
EOF

echo ""
echo "================================================"
echo "STEP 2: Get existing patient..."
php artisan tinker << 'EOF'
$patient = \DB::table('patients')->first();
if ($patient) {
    echo "✅ Using patient ID: " . $patient->id . "\n";
} else {
    echo "❌ No patient found!\n";
}
EOF

echo ""
echo "================================================"
echo "STEP 3: Create test Fiche Navette..."
php artisan tinker << 'EOF'
$patient = \DB::table('patients')->first();
$fiche = \DB::table('fiche_navettes')->insertGetId([
    'patient_id' => $patient->id,
    'date_reception' => now(),
    'total_amount' => 0,
    'payment_status' => 'unpaid',
    'status' => 'pending',
    'created_by' => 1,
    'updated_by' => 1,
    'created_at' => now(),
    'updated_at' => now(),
]);
echo "✅ Created Fiche ID: " . $fiche . "\n";

// Save for next steps
file_put_contents('/tmp/test_fiche_id.txt', $fiche);
EOF

echo ""
FICHE_ID=$(cat /tmp/test_fiche_id.txt)
echo "================================================"
echo "STEP 4: Add first Endoscopie (87)..."
php artisan tinker << EOF
\$ficheId = $FICHE_ID;
\$item1 = \DB::table('fiche_navette_items')->insertGetId([
    'fiche_navette_id' => \$ficheId,
    'prestation_id' => 87,
    'payment_status' => 'unpaid',
    'final_price' => 0,
    'status' => 'pending',
    'created_at' => now(),
    'updated_at' => now(),
]);
echo "✅ Added Item 1 (Endoscopie 87): " . \$item1 . "\n";
EOF

echo ""
echo "================================================"
echo "STEP 5: Add second Endoscopie (87)..."
php artisan tinker << EOF
\$ficheId = $FICHE_ID;
\$item2 = \DB::table('fiche_navette_items')->insertGetId([
    'fiche_navette_id' => \$ficheId,
    'prestation_id' => 87,
    'payment_status' => 'unpaid',
    'final_price' => 0,
    'status' => 'pending',
    'created_at' => now(),
    'updated_at' => now(),
]);
echo "✅ Added Item 2 (Endoscopie 87): " . \$item2 . "\n";
EOF

echo ""
echo "================================================"
echo "STEP 6: Verify current state..."
php artisan tinker << EOF
\$ficheId = $FICHE_ID;
\$items = \DB::table('fiche_navette_items')
    ->where('fiche_navette_id', \$ficheId)
    ->get();
echo "Items in fiche: " . \$items->count() . "\n";
foreach (\$items as \$item) {
    echo "- Item " . \$item->id . ": Prestation " . \$item->prestation_id . "\n";
}
EOF

echo ""
echo "================================================"
echo "STEP 7: TEST AUTO-CONVERSION - Add Pose d'implant (88)..."
echo "This should trigger auto-conversion to PACK CARDIOLOGIE 05"
echo ""

php artisan tinker << EOF
\$ficheId = $FICHE_ID;

// Simulate the auto-conversion check exactly as the controller does
\$fiche = \App\Models\Reception\ficheNavette::find(\$ficheId);

// Get existing prestation IDs
\$existingIds = \$fiche->items()
    ->whereNotNull('prestation_id')
    ->pluck('prestation_id')
    ->toArray();

// New prestation IDs being added
\$newIds = [88];

echo "Existing prestation IDs: " . json_encode(\$existingIds) . "\n";
echo "New prestation IDs: " . json_encode(\$newIds) . "\n";
echo "Combined IDs: " . json_encode(array_merge(\$existingIds, \$newIds)) . "\n";

// This is what ReceptionService does
\$service = app(\App\Services\Reception\ReceptionService::class);
\$conversionCheck = \$service->checkAndPreparePackageConversion(
    \$ficheId,
    \$newIds,
    \$existingIds
);

echo "\n=== CONVERSION CHECK RESULT ===\n";
echo "Should Convert: " . (\$conversionCheck['should_convert'] ? "✅ YES" : "❌ NO") . "\n";
echo "Package ID: " . (\$conversionCheck['package_id'] ?? "null") . "\n";
echo "Package Name: " . (\$conversionCheck['package_name'] ?? "null") . "\n";
echo "Message: " . (\$conversionCheck['message'] ?? "null") . "\n";

if (\$conversionCheck['should_convert']) {
    echo "\n✅ AUTO-CONVERSION TRIGGERED!\n";
    echo "Items to remove: " . json_encode(array_column(\$conversionCheck['items_to_remove'], 'id')) . "\n";
} else {
    echo "\n❌ Auto-conversion NOT triggered.\n";
    echo "Check logs for reason.\n";
}
EOF

echo ""
echo "================================================"
echo "STEP 8: Check logs for detailed info..."
echo ""
grep -i "conversion\|package\|auto" storage/logs/laravel.log | tail -20

echo ""
echo "================================================"
echo "STEP 9: Cleanup..."
php artisan tinker << EOF
\$ficheId = $FICHE_ID;
\DB::table('fiche_navette_items')->where('fiche_navette_id', \$ficheId)->delete();
\DB::table('fiche_navettes')->where('id', \$ficheId)->delete();
echo "✅ Test data cleaned up\n";
EOF

echo ""
echo "================================================"
echo "TEST COMPLETE"
echo "================================================"
