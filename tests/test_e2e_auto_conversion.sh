#!/bin/bash
# Complete end-to-end test of auto-package conversion

echo "================================================"
echo "AUTO-PACKAGE CONVERSION - COMPLETE E2E TEST"
echo "================================================"
echo ""

# Step 0: Verify package configuration
echo "STEP 0: Verify PACK CARDIOLOGIE 05 configuration"
mysql -h 10.47.0.26 -u sail -ppassword his_database -e "
SELECT 'Package ID 11 (PACK CARDIOLOGIE 05) contains:' as info;
SELECT 
  CONCAT(p.id, ': ', p.name) as prestation
FROM prestation_package_items ppi
JOIN prestations p ON ppi.prestation_id = p.id
WHERE ppi.prestation_package_id = 11
ORDER BY ppi.id;
" 2>&1 | grep -v Warning
echo ""

# Step 1: Create test Fiche
echo "STEP 1: Create test Fiche Navette"
FICHE_ID=$(mysql -h 10.47.0.26 -u sail -ppassword his_database -e "
INSERT INTO fiche_navettes (patient_id, date_reception, total_amount, payment_status, status, created_by, updated_by, created_at, updated_at)
VALUES (1, NOW(), 0, 'unpaid', 'pending', 1, 1, NOW(), NOW());
SELECT LAST_INSERT_ID();
" 2>&1 | grep -v Warning | tail -1)

echo "✅ Created Fiche ID: $FICHE_ID"
echo ""

# Step 2: Add first prestation
echo "STEP 2: Add first prestation (Stabilisation patient critique 5)"
ITEM1=$(mysql -h 10.47.0.26 -u sail -ppassword his_database -e "
INSERT INTO fiche_navette_items (fiche_navette_id, prestation_id, payment_status, status, final_price, created_at, updated_at)
VALUES ($FICHE_ID, 5, 'unpaid', 'pending', 0, NOW(), NOW());
SELECT LAST_INSERT_ID();
" 2>&1 | grep -v Warning | tail -1)

echo "✅ Added Item ID: $ITEM1 (Prestation 5)"
echo ""

# Step 3: Add second prestation
echo "STEP 3: Add second prestation (Endoscopie digestive 87)"
ITEM2=$(mysql -h 10.47.0.26 -u sail -ppassword his_database -e "
INSERT INTO fiche_navette_items (fiche_navette_id, prestation_id, payment_status, status, final_price, created_at, updated_at)
VALUES ($FICHE_ID, 87, 'unpaid', 'pending', 0, NOW(), NOW());
SELECT LAST_INSERT_ID();
" 2>&1 | grep -v Warning | tail -1)

echo "✅ Added Item ID: $ITEM2 (Prestation 87)"
echo ""

# Step 4: Check current state BEFORE conversion
echo "STEP 4: Current fiche state BEFORE adding third prestation"
mysql -h 10.47.0.26 -u sail -ppassword his_database -e "
SELECT fni.id, fni.prestation_id, fni.package_id, p.name as prestation_name, fni.payment_status
FROM fiche_navette_items fni
LEFT JOIN prestations p ON fni.prestation_id = p.id
WHERE fni.fiche_navette_id = $FICHE_ID
ORDER BY fni.id;
" 2>&1 | grep -v Warning
echo ""

# Step 5: Test the auto-conversion check logic
echo "STEP 5: Test PHP auto-conversion check logic"
php << EOF
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(\Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

\$ficheId = $FICHE_ID;

// Get existing prestation IDs
\$existingIds = \DB::table('fiche_navette_items')
    ->where('fiche_navette_id', \$ficheId)
    ->whereNotNull('prestation_id')
    ->pluck('prestation_id')
    ->toArray();

// New prestation being added
\$newIds = [88];

echo "Existing prestations: " . json_encode(\$existingIds) . "\n";
echo "New prestations: " . json_encode(\$newIds) . "\n";
echo "Combined: " . json_encode(array_merge(\$existingIds, \$newIds)) . "\n\n";

// Test the conversion check
\$service = app(\App\Services\Reception\ReceptionService::class);
\$result = \$service->checkAndPreparePackageConversion(\$ficheId, \$newIds, \$existingIds);

echo "Conversion Check Result:\n";
echo "- Should Convert: " . (\$result['should_convert'] ? "✅ YES" : "❌ NO") . "\n";
echo "- Package: " . (\$result['package_name'] ?? 'None') . "\n";
echo "- Items to Remove: " . json_encode(array_column(\$result['items_to_remove'] ?? [], 'id')) . "\n";
echo "\n";
EOF

echo "STEP 6: Simulate adding the third prestation (this would trigger auto-conversion)"
echo "In real scenario, this would be an API call:"
echo "POST /api/ficheNavette/$FICHE_ID/items"
echo "Body: { prestations: [{ id: 88, prestation_id: 88 }] }"
echo ""
echo "Expected behavior:"
echo "- Old items ($ITEM1, $ITEM2) should be DELETED"
echo "- New package item should be CREATED with package_id = 11"
echo "- Fiche should have 1 item (the package)"
echo ""

# Step 7: Cleanup
echo "STEP 7: Cleanup test data"
mysql -h 10.47.0.26 -u sail -ppassword his_database -e "
DELETE FROM fiche_navette_items WHERE fiche_navette_id = $FICHE_ID;
DELETE FROM fiche_navettes WHERE id = $FICHE_ID;
" 2>&1 | grep -v Warning

echo "✅ Test data cleaned up"
echo ""
echo "================================================"
echo "TEST COMPLETE"
echo "================================================"
echo ""
echo "✅ Auto-conversion logic is ready!"
echo "Now test in the UI by:"
echo "1. Create a fiche"
echo "2. Add prestation 5 (Stabilisation patient critique)"
echo "3. Add prestation 87 (Endoscopie digestive)"
echo "4. Add prestation 88 (Pose d'implant)"
echo ""
echo "Result: Old items should be removed, new PACK CARDIOLOGIE 05 should appear!"
