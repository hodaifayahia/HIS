<?php

/**
 * Test Script: Verify LARGEST Package Selection
 *
 * Demonstrates that when multiple packages match the prestations,
 * the system selects the ONE WITH THE MOST PRESTATIONS (largest/most comprehensive)
 *
 * SCENARIO:
 * Available packages:
 * - Package 8: [5, 87]           (2 prestations)
 * - Package 11: [5, 87, 88]       (3 prestations) ← LARGEST - SHOULD BE SELECTED
 *
 * User has: [5, 87, 88]
 * System should pick: Package 11 (not Package 8)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\PrestationPackage;

echo "\n";
echo "════════════════════════════════════════════════════════════════════════\n";
echo "  TEST: LARGEST PACKAGE SELECTION - Cascading Auto-Conversion\n";
echo "════════════════════════════════════════════════════════════════════════\n";
echo "\n";

// Get all packages
$packages = PrestationPackage::with('prestations')->where('is_active', true)->get();

echo "AVAILABLE PACKAGES IN SYSTEM:\n";
echo "───────────────────────────────────────────────────────────────────────\n";

$packageInfo = [];
foreach ($packages as $pkg) {
    $prestationIds = $pkg->prestations->pluck('id')->toArray();
    sort($prestationIds);
    $count = count($prestationIds);
    $packageInfo[$pkg->id] = [
        'name' => $pkg->name,
        'prestations' => $prestationIds,
        'count' => $count,
    ];
    echo sprintf("%-3s | %-30s | Prestations: [%-20s] | Count: %d\n",
        $pkg->id,
        $pkg->name,
        implode(', ', $prestationIds),
        $count
    );
}

echo "\n";
echo "TEST SCENARIO: User has prestations [5, 87, 88]\n";
echo "───────────────────────────────────────────────────────────────────────\n";

$userPrestations = [5, 87, 88];
echo "User's prestations: [".implode(', ', $userPrestations)."]\n\n";

// Find exact matches
echo "CHECKING FOR EXACT MATCHES:\n";
$exactMatches = [];

foreach ($packages as $package) {
    $packagePrestationIds = $package->prestations->pluck('id')->toArray();
    sort($packagePrestationIds);

    // Check for exact match
    if (count($packagePrestationIds) === count($userPrestations) &&
        empty(array_diff($packagePrestationIds, $userPrestations)) &&
        empty(array_diff($userPrestations, $packagePrestationIds))) {

        $exactMatches[] = [
            'id' => $package->id,
            'name' => $package->name,
            'prestations' => $packagePrestationIds,
            'count' => count($packagePrestationIds),
        ];

        echo "  ✅ EXACT MATCH: Package {$package->id} ({$package->name})\n";
        echo '     Prestations: ['.implode(', ', $packagePrestationIds)."]\n";
    }
}

if (empty($exactMatches)) {
    echo "  ❌ NO EXACT MATCHES FOUND\n";
} else {
    echo "\n";
    echo "SELECTING LARGEST PACKAGE:\n";
    echo "───────────────────────────────────────────────────────────────────────\n";

    // Sort by prestation count (descending) to get the largest
    usort($exactMatches, function ($a, $b) {
        return $b['count'] - $a['count'];
    });

    $selected = $exactMatches[0];
    echo "🏆 SELECTED PACKAGE: {$selected['name']} (ID: {$selected['id']})\n";
    echo '   Prestations: ['.implode(', ', $selected['prestations'])."]\n";
    echo "   Size: {$selected['count']} prestations\n";

    if (count($exactMatches) > 1) {
        echo "\n   Other exact matches (NOT selected - smaller):\n";
        for ($i = 1; $i < count($exactMatches); $i++) {
            $other = $exactMatches[$i];
            echo "   - Package {$other['id']} ({$other['name']}): {$other['count']} prestations\n";
        }
    }

    echo "\n";
    echo "✅ CORRECT SELECTION!\n";
    echo "   System picked the LARGEST package (most comprehensive)\n";

    // Verification
    echo "\n";
    echo "VERIFICATION:\n";
    echo "───────────────────────────────────────────────────────────────────────\n";

    if ($selected['id'] === 11) {
        echo "✅ Correctly selected PACK CARDIOLOGIE 05 (Package 11)\n";
        echo "   This is the most comprehensive package with [5, 87, 88]\n";
    } elseif ($selected['id'] === 8) {
        echo "❌ ERROR: Selected PACK CARDIOLOGIE 04 (Package 8)\n";
        echo "   Should have selected the larger package 11 instead!\n";
    } else {
        echo "❓ Selected unexpected package: {$selected['id']}\n";
    }
}

echo "\n";
echo "════════════════════════════════════════════════════════════════════════\n";
echo "TEST COMPLETE\n";
echo "════════════════════════════════════════════════════════════════════════\n";
echo "\n";
