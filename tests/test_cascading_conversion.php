#!/usr/bin/env php
<?php

/**
 * Cascading Auto-Conversion Test Script
 *
 * Tests the cascading auto-conversion feature:
 * 1. Add item 1 (prestation 5)
 * 2. Add item 2 (prestation 87) - may trigger first conversion
 * 3. Add item 3 (prestation 88) - should trigger cascading conversion
 *
 * Usage: php test_cascading_conversion.php
 */

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Prestation;
use App\Models\PrestationPackage;
use App\Services\Reception\ReceptionService;
use Illuminate\Support\Facades\DB;

$service = app(ReceptionService::class);

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  CASCADING AUTO-CONVERSION TEST\n";
echo "═══════════════════════════════════════════════════════════════\n";

try {
    // Check if packages exist
    echo "\n[SETUP] Checking required packages and prestations...\n";

    $prestation5 = Prestation::find(5);
    $prestation87 = Prestation::find(87);
    $prestation88 = Prestation::find(88);
    $packCardio = PrestationPackage::find(11);

    if (! $prestation5 || ! $prestation87 || ! $prestation88) {
        echo "❌ ERROR: Required prestations not found\n";
        echo '   Prestation 5: '.($prestation5 ? '✓ Found' : '✗ Missing')."\n";
        echo '   Prestation 87: '.($prestation87 ? '✓ Found' : '✗ Missing')."\n";
        echo '   Prestation 88: '.($prestation88 ? '✓ Found' : '✗ Missing')."\n";
        exit(1);
    }

    if (! $packCardio) {
        echo "❌ ERROR: PACK CARDIOLOGIE 05 (ID: 11) not found\n";
        exit(1);
    }

    echo '✓ Prestation 5: '.$prestation5->name."\n";
    echo '✓ Prestation 87: '.$prestation87->name."\n";
    echo '✓ Prestation 88: '.$prestation88->name."\n";
    echo "✓ Package: PACK CARDIOLOGIE 05 (ID: 11)\n";
    echo '  - Active: '.($packCardio->is_active ? 'Yes ✓' : 'No ✗')."\n";

    // Check package prestations
    $packagePrestations = DB::table('prestation_package_items')
        ->where('prestation_package_id', 11)
        ->pluck('prestation_id')
        ->toArray();

    echo '  - Prestations: '.implode(', ', $packagePrestations)."\n";

    if (! in_array(5, $packagePrestations) || ! in_array(87, $packagePrestations) || ! in_array(88, $packagePrestations)) {
        echo "❌ WARNING: Package 11 does not have all required prestations [5, 87, 88]\n";
        echo '   Current: ['.implode(', ', $packagePrestations)."]\n";
        echo "   Please ensure package is properly configured\n";
    }

    // Also check Package 8 (PACK CARDIOLOGIE 04)
    echo "\n✓ Package 8 (PACK CARDIOLOGIE 04) - First conversion package:\n";
    $pack8Prestations = DB::table('prestation_package_items')
        ->where('prestation_package_id', 8)
        ->pluck('prestation_id')
        ->toArray();
    echo '  - Prestations: '.implode(', ', $pack8Prestations)."\n";

    // TEST: Check conversion detection for [5, 87, 88]
    echo "\n[TEST 1] Testing cascading conversion detection\n";
    echo "─────────────────────────────────────────────────────────────\n";

    $result = $service->checkAndPreparePackageConversion(
        ficheNavetteId: 4,  // Use a real fiche ID or a test one
        newPrestationIds: [88],
        existingPrestationIds: [5, 87]
    );

    echo "Scenario: Existing items [5, 87] + New item [88]\n";
    echo "Result:\n";
    echo '  Should Convert: '.($result['should_convert'] ? '✅ YES' : '❌ NO')."\n";

    if ($result['should_convert']) {
        echo '  Package ID: '.$result['package_id']."\n";
        echo '  Package Name: '.$result['package_name']."\n";
        echo '  Is Cascading: '.(($result['is_cascading'] ?? false) ? '✅ YES' : 'No')."\n";
        echo '  Items to Remove: '.count($result['items_to_remove'])."\n";
        echo '  Message: '.$result['message']."\n";
    } else {
        echo '  Message: '.$result['message']."\n";
    }

    // Determine test result
    $testPassed = $result['should_convert']
        && $result['package_id'] == 11
        && $result['package_name'] == 'PACK CARDIOLOGIE 05';

    echo "\n".($testPassed ? '✅ TEST PASSED' : '❌ TEST FAILED')."\n";

    // Additional info
    echo "\n[INFO] Cascading Logic Details\n";
    echo "─────────────────────────────────────────────────────────────\n";

    if ($result['should_convert']) {
        echo "Cascading Information:\n";
        echo '  - Will remove '.count($result['items_to_remove'])." items\n";

        if (isset($result['existing_package_items']) && ! empty($result['existing_package_items'])) {
            echo '  - Will replace '.count($result['existing_package_items'])." existing package(s)\n";
        }

        echo "\nThe system will:\n";
        echo "  1. ✓ Detect existing items [5, 87]\n";
        echo "  2. ✓ Extract their prestations (from package or individual items)\n";
        echo "  3. ✓ Combine with new item [88] → [5, 87, 88]\n";
        echo "  4. ✓ Match to PACK CARDIOLOGIE 05\n";
        echo "  5. ✓ Remove old items/packages\n";
        echo "  6. ✓ Create new PACK CARDIOLOGIE 05 item\n";
    }

    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  TEST COMPLETE\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";

    exit($testPassed ? 0 : 1);

} catch (Exception $e) {
    echo "\n❌ ERROR: ".$e->getMessage()."\n";
    echo 'File: '.$e->getFile()."\n";
    echo 'Line: '.$e->getLine()."\n";
    echo "\nTrace:\n".$e->getTraceAsString()."\n";
    exit(1);
}
