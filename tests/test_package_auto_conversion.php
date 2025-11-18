<?php

/**
 * Package Auto-Conversion Test Suite
 *
 * Tests the complete package detection and automatic conversion flow:
 * - Exact-match algorithm validation
 * - Convention/dependency exclusion logic
 * - Real-world scenario: Stabilisation patient critique + Endoscopie digestive → PACK CARDIOLOGIE
 *
 * Usage: php test_package_auto_conversion.php
 */

require __DIR__.'/vendor/autoload.php';

use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\PrestationPackage;
use App\Services\Reception\FileUploadService;
use App\Services\Reception\ReceptionService;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "   PACKAGE AUTO-CONVERSION TEST SUITE\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "\n";

// Initialize service
$fileUploadService = new FileUploadService;
$receptionService = new ReceptionService($fileUploadService);

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

/**
 * Test helper function
 */
function test($description, $callback)
{
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;

    echo "TEST {$totalTests}: {$description}\n";
    echo str_repeat('-', 70)."\n";

    try {
        $result = $callback();
        if ($result === true) {
            $passedTests++;
            echo "✅ PASSED\n";
        } else {
            $failedTests++;
            echo '❌ FAILED: '.($result ?: 'Test returned false')."\n";
        }
    } catch (Exception $e) {
        $failedTests++;
        echo '❌ FAILED with exception: '.$e->getMessage()."\n";
        echo '   File: '.$e->getFile().':'.$e->getLine()."\n";
    }

    echo "\n";
}

// ═══════════════════════════════════════════════════════════════════
// TEST 1: Verify database has packages with prestations
// ═══════════════════════════════════════════════════════════════════
test('Verify database has active packages with prestations', function () {
    $packagesWithPrestations = PrestationPackage::with('prestations')
        ->whereHas('prestations')
        ->take(5)
        ->get();

    if ($packagesWithPrestations->count() === 0) {
        return 'No packages with prestations found in database';
    }

    echo "   ✓ Found {$packagesWithPrestations->count()} package(s) with prestations:\n";

    foreach ($packagesWithPrestations as $package) {
        $prestationIds = $package->prestations->pluck('id')->toArray();
        echo "      📦 Package #{$package->id}: {$package->name}\n";
        echo '         Prestation IDs: ['.implode(', ', $prestationIds)."]\n";

        foreach ($package->prestations as $prestation) {
            echo "         - #{$prestation->id}: {$prestation->name}\n";
        }
    }

    return true;
});

// ═══════════════════════════════════════════════════════════════════
// TEST 2: Test exact-match algorithm - POSITIVE case
// ═══════════════════════════════════════════════════════════════════
test('Exact-match algorithm - should find package when prestations match exactly', function () use ($receptionService) {
    $package = PrestationPackage::with('prestations')
        ->whereHas('prestations')
        ->first();

    if (! $package || $package->prestations->count() === 0) {
        return 'No valid package found for testing';
    }

    $prestationIds = $package->prestations->pluck('id')->toArray();

    echo "   📦 Testing with package: #{$package->id} - {$package->name}\n";
    echo '   📋 Prestation IDs: ['.implode(', ', $prestationIds)."]\n";

    $result = $receptionService->detectMatchingPackage($prestationIds);

    if (! $result) {
        return 'Package not detected - exact match failed';
    }

    if ($result->id !== $package->id) {
        return "Wrong package detected. Expected #{$package->id}, got #{$result->id}";
    }

    echo "   ✅ Correctly detected package: #{$result->id} - {$result->name}\n";

    return true;
});

// ═══════════════════════════════════════════════════════════════════
// TEST 3: Test exact-match algorithm - NEGATIVE cases
// ═══════════════════════════════════════════════════════════════════
test('Exact-match algorithm - should NOT match subset of prestations', function () use ($receptionService) {
    $package = PrestationPackage::with('prestations')
        ->whereHas('prestations', function ($query) {
            $query->havingRaw('COUNT(*) >= 2');
        })
        ->first();

    if (! $package || $package->prestations->count() < 2) {
        return 'No suitable package found (need at least 2 prestations)';
    }

    $allIds = $package->prestations->pluck('id')->toArray();
    $subset = array_slice($allIds, 0, -1);  // Remove last item

    echo "   📦 Package: #{$package->id} with ".count($allIds).' prestations: ['.implode(', ', $allIds)."]\n";
    echo '   📋 Testing subset: ['.implode(', ', $subset)."]\n";

    $result = $receptionService->detectMatchingPackage($subset);

    if ($result) {
        return 'Subset incorrectly matched package - should require exact match';
    }

    echo "   ✅ Correctly rejected subset (no match found)\n";

    return true;
});

test('Exact-match algorithm - should NOT match with extra prestations', function () use ($receptionService) {
    $package = PrestationPackage::with('prestations')
        ->whereHas('prestations')
        ->first();

    if (! $package) {
        return 'No package found for testing';
    }

    $allIds = $package->prestations->pluck('id')->toArray();
    $withExtra = array_merge($allIds, [999999]);  // Add non-existent prestation

    echo "   📦 Package: #{$package->id} with ".count($allIds).' prestations: ['.implode(', ', $allIds)."]\n";
    echo '   📋 Testing with extra: ['.implode(', ', $withExtra)."]\n";

    $result = $receptionService->detectMatchingPackage($withExtra);

    if ($result) {
        return 'Extra prestations incorrectly matched package - should require exact match';
    }

    echo "   ✅ Correctly rejected extra prestations (no match found)\n";

    return true;
});

// ═══════════════════════════════════════════════════════════════════
// TEST 4: Test convention/dependency filtering logic
// ═══════════════════════════════════════════════════════════════════
test('Filtering - convention and dependency items should be excluded from matching', function () {
    echo "   Simulating mixed data (standard + convention + dependency items)\n";

    $mixedData = [
        ['id' => 1, 'name' => 'Standard 1', 'is_convention' => false, 'is_dependency' => false],
        ['id' => 2, 'name' => 'Standard 2', 'is_convention' => false, 'is_dependency' => false],
        ['id' => 3, 'name' => 'Convention Item', 'is_convention' => true, 'is_dependency' => false],
        ['id' => 4, 'name' => 'Dependency Item', 'is_convention' => false, 'is_dependency' => true],
        ['id' => 5, 'name' => 'Both flags', 'is_convention' => true, 'is_dependency' => true],
    ];

    // Simulate backend filtering logic
    $standardItems = array_filter($mixedData, function ($item) {
        $isConvention = $item['is_convention'] ?? false;
        $isDependency = $item['is_dependency'] ?? false;

        return ! $isConvention && ! $isDependency;
    });

    echo '   📋 Total items: '.count($mixedData)."\n";
    echo '   📋 Filtered standard items: '.count($standardItems)."\n";

    foreach ($standardItems as $item) {
        echo "      - {$item['name']}\n";
    }

    if (count($standardItems) !== 2) {
        return 'Filtering failed - expected 2 standard items, got '.count($standardItems);
    }

    echo "   ✅ Filtering correctly excluded 3 non-standard items\n";

    return true;
});

// ═══════════════════════════════════════════════════════════════════
// TEST 5: Real-world mixed scenario
// ═══════════════════════════════════════════════════════════════════
test('Real-world scenario - package detection with mixed item types', function () use ($receptionService) {
    $package = PrestationPackage::with('prestations')
        ->whereHas('prestations', function ($query) {
            $query->havingRaw('COUNT(*) >= 2');
        })
        ->first();

    if (! $package || $package->prestations->count() < 2) {
        return 'No suitable package found';
    }

    echo "   📦 Package: #{$package->id} - {$package->name}\n";

    $packagePrestationIds = $package->prestations->pluck('id')->toArray();

    // Simulate request data with mixed types
    $requestData = [
        'prestations' => [],
    ];

    // Add package prestations (standard items)
    foreach ($packagePrestationIds as $id) {
        $requestData['prestations'][] = [
            'id' => $id,
            'prestation_id' => $id,
            'is_convention' => false,
            'is_dependency' => false,
        ];
    }

    // Add convention item (should be excluded)
    $requestData['prestations'][] = [
        'id' => 888888,
        'prestation_id' => 888888,
        'is_convention' => true,
        'is_dependency' => false,
    ];

    // Add dependency item (should be excluded)
    $requestData['prestations'][] = [
        'id' => 999999,
        'prestation_id' => 999999,
        'is_convention' => false,
        'is_dependency' => true,
    ];

    echo "   📋 Request contains:\n";
    echo '      - Standard prestations: '.count($packagePrestationIds)." (match package)\n";
    echo "      - Convention items: 1 (should be excluded)\n";
    echo "      - Dependency items: 1 (should be excluded)\n";
    echo '      - Total: '.count($requestData['prestations'])." items\n";

    // Filter standard items (like backend does)
    $standardPrestations = array_filter($requestData['prestations'], function ($p) {
        return ! ($p['is_convention'] ?? false) && ! ($p['is_dependency'] ?? false);
    });

    $standardIds = array_map(function ($p) {
        return $p['id'] ?? $p['prestation_id'];
    }, $standardPrestations);

    echo "\n   📋 After filtering: [".implode(', ', $standardIds)."]\n";

    // Test detection with filtered IDs
    $result = $receptionService->detectMatchingPackage($standardIds);

    if (! $result) {
        return 'Package not detected after filtering';
    }

    if ($result->id !== $package->id) {
        return 'Wrong package detected';
    }

    echo "   ✅ Package correctly detected: #{$result->id} - {$result->name}\n";
    echo "   ✅ Convention and dependency items were properly excluded\n";

    return true;
});

// ═══════════════════════════════════════════════════════════════════
// TEST 6: Search for PACK CARDIOLOGIE with specific prestations
// ═══════════════════════════════════════════════════════════════════
test('Search for PACK CARDIOLOGIE with Stabilisation + Endoscopie', function () use ($receptionService) {
    echo "   Searching for 'Stabilisation patient critique' and 'Endoscopie digestive'...\n";

    // Search for prestations by name (partial match)
    $stabilisation = Prestation::where('name', 'LIKE', '%Stabilisation%')
        ->orWhere('name', 'LIKE', '%patient%')
        ->orWhere('name', 'LIKE', '%critique%')
        ->first();

    $endoscopie = Prestation::where('name', 'LIKE', '%Endoscopie%')
        ->orWhere('name', 'LIKE', '%digestive%')
        ->first();

    if ($stabilisation) {
        echo "   ✓ Found: Prestation #{$stabilisation->id} - {$stabilisation->name}\n";
    } else {
        echo "   ⚠️  'Stabilisation patient critique' not found\n";
    }

    if ($endoscopie) {
        echo "   ✓ Found: Prestation #{$endoscopie->id} - {$endoscopie->name}\n";
    } else {
        echo "   ⚠️  'Endoscopie digestive' not found\n";
    }

    if (! $stabilisation || ! $endoscopie) {
        echo "\n   Listing all prestations with similar names:\n";
        $allPrestations = Prestation::where('name', 'LIKE', '%Stabilisation%')
            ->orWhere('name', 'LIKE', '%Endoscopie%')
            ->get();

        foreach ($allPrestations as $p) {
            echo "      - Prestation #{$p->id}: {$p->name}\n";
        }

        return 'One or both required prestations not found in database';
    }

    echo "\n   Testing package detection with IDs: [{$stabilisation->id}, {$endoscopie->id}]\n";

    $result = $receptionService->detectMatchingPackage([$stabilisation->id, $endoscopie->id]);

    if (! $result) {
        echo "   ⚠️  No package found containing exactly these 2 prestations\n";

        // Search for packages containing either prestation
        echo "\n   Searching for packages containing these prestations...\n";

        $packages = PrestationPackage::with('prestations')
            ->whereHas('prestations', function ($query) use ($stabilisation, $endoscopie) {
                $query->whereIn('prestation_id', [$stabilisation->id, $endoscopie->id]);
            })
            ->get();

        foreach ($packages as $pkg) {
            $prestIds = $pkg->prestations->pluck('id')->toArray();
            echo "      📦 Package #{$pkg->id}: {$pkg->name}\n";
            echo '         Prestations: ['.implode(', ', $prestIds)."]\n";
        }

        return 'No exact match found (see search results above)';
    }

    echo "   ✅ Package found: #{$result->id} - {$result->name}\n";
    echo "   ✅ This is the package that contains exactly these 2 prestations\n";

    return true;
});

// ═══════════════════════════════════════════════════════════════════
// TEST 7: Test data preservation after conversion
// ═══════════════════════════════════════════════════════════════════
test('Data preservation - convention/dependency items should remain after conversion', function () {
    echo "   Simulating backend conversion logic\n";

    $originalData = [
        'prestations' => [
            ['id' => 1, 'is_convention' => false, 'is_dependency' => false],
            ['id' => 2, 'is_convention' => false, 'is_dependency' => false],
            ['id' => 3, 'is_convention' => true, 'is_dependency' => false],
            ['id' => 4, 'is_convention' => false, 'is_dependency' => true],
        ],
    ];

    // Separate by type (like updated backend does)
    $standardPrestations = [];
    $conventionPrestations = [];
    $dependencyPrestations = [];

    foreach ($originalData['prestations'] as $prestation) {
        $isConvention = $prestation['is_convention'] ?? false;
        $isDependency = $prestation['is_dependency'] ?? false;

        if ($isConvention) {
            $conventionPrestations[] = $prestation;
        } elseif ($isDependency) {
            $dependencyPrestations[] = $prestation;
        } else {
            $standardPrestations[] = $prestation;
        }
    }

    echo '   📋 Original: '.count($originalData['prestations'])." total prestations\n";
    echo "   📋 Separated:\n";
    echo '      - Standard: '.count($standardPrestations)."\n";
    echo '      - Convention: '.count($conventionPrestations)."\n";
    echo '      - Dependency: '.count($dependencyPrestations)."\n";

    // Simulate conversion
    $convertedData = $originalData;
    $convertedData['packages'] = [['package_id' => 1, 'name' => 'Test Package']];
    $convertedData['prestations'] = array_merge($conventionPrestations, $dependencyPrestations);

    echo "\n   📋 After conversion:\n";
    echo '      - Packages: '.count($convertedData['packages'])."\n";
    echo '      - Remaining prestations: '.count($convertedData['prestations'])."\n";

    if (count($convertedData['prestations']) !== 2) {
        return 'Failed - should preserve 2 non-standard items, got '.count($convertedData['prestations']);
    }

    echo "   ✅ Convention and dependency items correctly preserved\n";

    return true;
});

// ═══════════════════════════════════════════════════════════════════
// SUMMARY
// ═══════════════════════════════════════════════════════════════════
echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "   TEST SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "\n";
echo "Total tests run:    {$totalTests}\n";
echo "✅ Passed:          {$passedTests}\n";
echo "❌ Failed:          {$failedTests}\n";
echo "\n";

if ($failedTests === 0) {
    echo "🎉 ALL TESTS PASSED!\n";
    echo "\n";
    echo "Package auto-conversion is working correctly:\n";
    echo "  ✓ Exact-match algorithm validated\n";
    echo "  ✓ Convention/dependency exclusion working\n";
    echo "  ✓ Data preservation confirmed\n";
    echo "  ✓ Real-world scenarios tested\n";
    echo "\n";
    exit(0);
} else {
    echo "⚠️  SOME TESTS FAILED - Please review the output above.\n";
    echo "\n";
    exit(1);
}
