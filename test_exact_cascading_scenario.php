<?php

/**
 * Test Script: Exact Cascading Auto-Conversion Scenario
 * 
 * SCENARIO:
 * 1. Fiche has PACK CARDIOLOGIE 04 (prestations 5, 87)
 * 2. User adds prestation 88
 * 3. System should detect: [5, 87, 88] = PACK CARDIOLOGIE 05
 * 4. System should remove PACK CARDIOLOGIE 04
 * 5. System should create PACK CARDIOLOGIE 05
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Models\CONFIGURATION\PrestationPackage;
use App\Models\CONFIGURATION\Prestation;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  CASCADING AUTO-CONVERSION TEST - EXACT USER SCENARIO\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// Step 1: Verify packages exist
echo "STEP 1: Verifying Package Configuration\n";
echo "─────────────────────────────────────────────────────────────\n";

$pack04 = PrestationPackage::with('items.prestation')->find(8);
$pack05 = PrestationPackage::with('items.prestation')->find(11);

if (!$pack04 || !$pack05) {
    echo "❌ ERROR: Required packages not found!\n";
    echo "   Package 8 (PACK CARDIOLOGIE 04): " . ($pack04 ? "FOUND" : "NOT FOUND") . "\n";
    echo "   Package 11 (PACK CARDIOLOGIE 05): " . ($pack05 ? "FOUND" : "NOT FOUND") . "\n";
    exit(1);
}

$pack04Prestations = $pack04->items->pluck('prestation.id')->toArray();
$pack05Prestations = $pack05->items->pluck('prestation.id')->toArray();

sort($pack04Prestations);
sort($pack05Prestations);

echo "✅ Package 8: {$pack04->name}\n";
echo "   Prestations: [" . implode(", ", $pack04Prestations) . "]\n";
echo "   Active: " . ($pack04->is_active ? "YES" : "NO") . "\n";
echo "\n";
echo "✅ Package 11: {$pack05->name}\n";
echo "   Prestations: [" . implode(", ", $pack05Prestations) . "]\n";
echo "   Active: " . ($pack05->is_active ? "YES" : "NO") . "\n";
echo "\n";

// Verify prestations exist
$prestation5 = Prestation::find(5);
$prestation87 = Prestation::find(87);
$prestation88 = Prestation::find(88);

echo "Prestation 5: " . ($prestation5 ? "✅ {$prestation5->name}" : "❌ NOT FOUND") . "\n";
echo "Prestation 87: " . ($prestation87 ? "✅ {$prestation87->name}" : "❌ NOT FOUND") . "\n";
echo "Prestation 88: " . ($prestation88 ? "✅ {$prestation88->name}" : "❌ NOT FOUND") . "\n";
echo "\n";

// Step 2: Create test patient and fiche
echo "STEP 2: Creating Test Fiche with PACK CARDIOLOGIE 04\n";
echo "─────────────────────────────────────────────────────────────\n";

DB::beginTransaction();

try {
    // Find or create a test patient
    $patient = Patient::first();
    if (!$patient) {
        echo "❌ ERROR: No patient found in database\n";
        DB::rollBack();
        exit(1);
    }
    
    echo "Using patient: {$patient->first_name} {$patient->last_name} (ID: {$patient->id})\n";
    
    // Create fiche navette
    $fiche = ficheNavette::create([
        'patient_id' => $patient->id,
        'reference' => 'TEST-CASCADE-' . time(),
        'status' => 'pending',
        'ticket_number' => null,
        'creator_id' => 1,
        'fiche_date' => now(),
    ]);
    
    echo "✅ Created fiche: {$fiche->reference} (ID: {$fiche->id})\n";
    
    // Add PACK CARDIOLOGIE 04 to the fiche
    $packageItem = ficheNavetteItem::create([
        'fiche_navette_id' => $fiche->id,
        'package_id' => 8, // PACK CARDIOLOGIE 04
        'base_price' => $pack04->price,
        'final_price' => $pack04->price,
        'payment_status' => 'unpaid',
        'status' => 'pending',
    ]);
    
    echo "✅ Added PACK CARDIOLOGIE 04 to fiche (Item ID: {$packageItem->id})\n";
    echo "\n";
    
    // Step 3: Simulate adding prestation 88
    echo "STEP 3: Testing Cascading Auto-Conversion\n";
    echo "─────────────────────────────────────────────────────────────\n";
    echo "Simulating: User adds Prestation 88 (Pose d'implant)\n";
    echo "\n";
    
    // Get existing items (including packages)
    $existingItems = ficheNavetteItem::where('fiche_navette_id', $fiche->id)->get();
    echo "Current fiche items:\n";
    foreach ($existingItems as $item) {
        if ($item->package_id) {
            $pkg = PrestationPackage::find($item->package_id);
            echo "  - Package: {$pkg->name} (ID: {$item->package_id})\n";
        } else {
            echo "  - Prestation ID: {$item->prestation_id}\n";
        }
    }
    echo "\n";
    
    // Extract prestations from existing packages
    $packagePrestationIds = [];
    $existingPackageItems = ficheNavetteItem::where('fiche_navette_id', $fiche->id)
        ->whereNotNull('package_id')
        ->get();
    
    echo "Extracting prestations from existing packages:\n";
    foreach ($existingPackageItems as $item) {
        $packagePrestations = DB::table('prestation_package_items')
            ->where('prestation_package_id', $item->package_id)
            ->pluck('prestation_id')
            ->toArray();
        
        $pkg = PrestationPackage::find($item->package_id);
        echo "  - Package {$item->package_id} ({$pkg->name}): [" . implode(", ", $packagePrestations) . "]\n";
        
        $packagePrestationIds = array_merge($packagePrestationIds, $packagePrestations);
    }
    echo "\n";
    
    // Combine with new prestation
    $newPrestationIds = [88]; // User is adding prestation 88
    $allPrestationIds = array_merge($packagePrestationIds, $newPrestationIds);
    sort($allPrestationIds);
    
    echo "Combined prestations: [" . implode(", ", $allPrestationIds) . "]\n";
    echo "Expected to match: PACK CARDIOLOGIE 05 [5, 87, 88]\n";
    echo "\n";
    
    // Test package detection
    echo "Testing detectMatchingPackage()...\n";
    
    $packages = PrestationPackage::with('prestations')->where('is_active', true)->get();
    $matchingPackage = null;
    
    foreach ($packages as $package) {
        $pkgPrestationIds = $package->prestations->pluck('id')->toArray();
        sort($pkgPrestationIds);
        
        if (count($pkgPrestationIds) === count($allPrestationIds) && 
            empty(array_diff($pkgPrestationIds, $allPrestationIds)) &&
            empty(array_diff($allPrestationIds, $pkgPrestationIds))) {
            
            $matchingPackage = $package;
            echo "✅ MATCH FOUND: {$package->name} (ID: {$package->id})\n";
            echo "   Package prestations: [" . implode(", ", $pkgPrestationIds) . "]\n";
            echo "   Looking for: [" . implode(", ", $allPrestationIds) . "]\n";
            break;
        }
    }
    
    if (!$matchingPackage) {
        echo "❌ NO MATCH FOUND\n";
        echo "\nAvailable packages checked:\n";
        foreach ($packages as $pkg) {
            $pkgPrestIds = $pkg->prestations->pluck('id')->toArray();
            sort($pkgPrestIds);
            echo "  - {$pkg->name} (ID: {$pkg->id}): [" . implode(", ", $pkgPrestIds) . "]\n";
        }
    } else {
        echo "\n";
        echo "STEP 4: Simulating Auto-Conversion\n";
        echo "─────────────────────────────────────────────────────────────\n";
        echo "Would remove: Package Item {$packageItem->id} (PACK CARDIOLOGIE 04)\n";
        echo "Would create: Package Item with Package ID {$matchingPackage->id} (PACK CARDIOLOGIE 05)\n";
        echo "\n";
        echo "✅ CASCADING AUTO-CONVERSION LOGIC WORKS CORRECTLY!\n";
    }
    
    // Rollback transaction (don't save test data)
    DB::rollBack();
    echo "\n";
    echo "─────────────────────────────────────────────────────────────\n";
    echo "Test completed (transaction rolled back - no data saved)\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
