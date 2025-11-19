#!/bin/bash
# Test auto-package conversion using an existing fiche

echo "================================================"
echo "AUTO-PACKAGE CONVERSION - FINAL TEST"
echo "================================================"
echo ""

# Get an existing fiche ID
FICHE_ID=4

echo "Using existing Fiche ID: $FICHE_ID"
echo ""

# Step 1: Show current state
echo "STEP 1: Current fiche items"
mysql -h 10.47.0.26 -u sail -ppassword his_database -e "
SELECT 
  fni.id,
  fni.prestation_id,
  fni.package_id,
  COALESCE(p.name, pkg.name) as item_name,
  fni.payment_status
FROM fiche_navette_items fni
LEFT JOIN prestations p ON fni.prestation_id = p.id
LEFT JOIN prestation_packages pkg ON fni.package_id = pkg.id
WHERE fni.fiche_navette_id = $FICHE_ID
ORDER BY fni.id;
" 2>&1 | grep -v Warning
echo ""

# Step 2: Test the PHP logic
echo "STEP 2: Test auto-conversion detection for Fiche $FICHE_ID"
php << EOF
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(\Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

echo "Testing auto-conversion scenarios...\n\n";

// Scenario 1: [5, 87, 88] - SHOULD match package 11
echo "=== SCENARIO 1: Adding items [5, 87, 88] ===\n";
\$service = app(\App\Services\Reception\ReceptionService::class);
\$result = \$service->checkAndPreparePackageConversion(4, [88], [5, 87]);
echo "Should Convert: " . (\$result['should_convert'] ? "✅ YES" : "❌ NO") . "\n";
echo "Package: " . (\$result['package_name'] ?? 'None') . "\n";
echo "Package ID: " . (\$result['package_id'] ?? 'None') . "\n\n";

// Scenario 2: [87, 87, 88] - Should NOT match any package currently
echo "=== SCENARIO 2: Adding items [87, 87, 88] ===\n";
\$result = \$service->checkAndPreparePackageConversion(4, [88], [87, 87]);
echo "Should Convert: " . (\$result['should_convert'] ? "✅ YES" : "❌ NO") . "\n";
echo "Package: " . (\$result['package_name'] ?? 'None') . "\n";
echo "Message: " . (\$result['message'] ?? 'None') . "\n\n";
EOF

echo "================================================"
echo "STEP 3: Instructions for manual testing"
echo "================================================"
echo ""
echo "To test the actual auto-conversion in the UI:"
echo ""
echo "1. Open Reception → Fiche Navette"
echo "2. Create a new Fiche or use an existing empty one"
echo "3. Add these prestations ONE BY ONE:"
echo "   - Prestation 5: Stabilisation patient critique 5"
echo "   - Prestation 87: Endoscopie digestive 87"
echo "   - Prestation 88: Pose d'implant 88"
echo ""
echo "EXPECTED RESULT:"
echo "✅ When you add the 3rd prestation (88), the system will:"
echo "   1. Detect that [5, 87, 88] matches PACK CARDIOLOGIE 05"
echo "   2. REMOVE the 2 previously added items (5 and 87)"
echo "   3. CREATE a new package item with PACK CARDIOLOGIE 05"
echo "   4. Show success message: 'Items added and auto-converted to package'"
echo ""
echo "================================================"
