<?php

/**
 * Advanced Test: Mixed Items Cascading
 * 
 * Verifies that when you have a MIX of:
 * - Package items
 * - Individual prestation items
 * 
 * And you add a new prestation that triggers a larger package,
 * ALL old items (both package AND individual) are removed
 * and replaced with the new package
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Models\CONFIGURATION\PrestationPackage;
use App\Models\CONFIGURATION\Prestation;
use App\Models\Patient;
use App\Services\Reception\ReceptionService;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "════════════════════════════════════════════════════════════════════════\n";
echo "  ADVANCED TEST: Mixed Items Cascading (Package + Individual Items)\n";
echo "════════════════════════════════════════════════════════════════════════\n";
echo "\n";

$receptionService = app(ReceptionService::class);

DB::beginTransaction();

try {
    echo "SCENARIO: Fiche has mixed items\n";
    echo "───────────────────────────────────────────────────────────────────────\n\n";
    
    $patient = Patient::first();
    
    // Create fiche
    $fiche = ficheNavette::create([
        'patient_id' => $patient->id,
        'reference' => 'MIXED-' . time(),
        'status' => 'pending',
        'creator_id' => 1,
        'fiche_date' => now(),
    ]);
    
    echo "Created fiche: {$fiche->reference} (ID: {$fiche->id})\n\n";
    
    // Add PACK CARDIOLOGIE 04 (contains 5, 87)
    $item1 = ficheNavetteItem::create([
        'fiche_navette_id' => $fiche->id,
        'package_id' => 8,
        'base_price' => 15000,
        'final_price' => 15000,
        'payment_status' => 'unpaid',
        'status' => 'pending',
    ]);
    
    echo "Step 1: Added PACK CARDIOLOGIE 04 [5, 87]\n";
    echo "  - Item ID: {$item1->id}\n";
    echo "  - Package ID: 8\n\n";
    
    // Optionally add individual prestation items that match the package
    $item2 = ficheNavetteItem::create([
        'fiche_navette_id' => $fiche->id,
        'prestation_id' => 5,
        'base_price' => 8000,
        'final_price' => 8000,
        'payment_status' => 'unpaid',
        'status' => 'pending',
    ]);
    
    echo "Step 2: Added individual prestation 5 (Stabilisation)\n";
    echo "  - Item ID: {$item2->id}\n";
    echo "  - Prestation ID: 5\n\n";
    
    // Check current state
    $currentItems = ficheNavetteItem::where('fiche_navette_id', $fiche->id)->get();
    echo "Current fiche items: " . $currentItems->count() . "\n";
    foreach ($currentItems as $item) {
        if ($item->package_id) {
            echo "  - Package item: package_id={$item->package_id}\n";
        } else {
            echo "  - Individual item: prestation_id={$item->prestation_id}\n";
        }
    }
    echo "\n";
    
    // Now trigger cascading by adding prestation 88
    echo "Step 3: Trigger cascading - Add prestation 88\n";
    echo "───────────────────────────────────────────────────────────────────────\n\n";
    
    $newPrestationIds = [88];
    $existingPrestationIds = [5]; // Individual prestation item
    
    echo "Calling checkAndPreparePackageConversion()...\n";
    echo "  Existing individual prestations: [5]\n";
    echo "  New prestations: [88]\n";
    echo "  Package prestations: [5, 87] (from PACK CARDIOLOGIE 04)\n\n";
    
    $conversionCheck = $receptionService->checkAndPreparePackageConversion(
        $fiche->id,
        $newPrestationIds,
        $existingPrestationIds
    );
    
    echo "Conversion check result:\n";
    echo "  should_convert: " . ($conversionCheck['should_convert'] ? 'YES ✅' : 'NO ❌') . "\n";
    echo "  is_cascading: " . (($conversionCheck['is_cascading'] ?? false) ? 'YES ✅' : 'NO ❌') . "\n";
    echo "  package_id: " . ($conversionCheck['package_id'] ?? 'N/A') . "\n";
    echo "  items_to_remove: " . count($conversionCheck['items_to_remove']) . " items\n";
    
    if (!$conversionCheck['should_convert']) {
        throw new Exception('Conversion check failed!');
    }
    
    echo "\nItems that will be removed:\n";
    foreach ($conversionCheck['items_to_remove'] as $item) {
        if ($item['package_id']) {
            echo "  - Package item ID {$item['id']} (package_id={$item['package_id']})\n";
        } else {
            echo "  - Individual item ID {$item['id']} (prestation_id={$item['prestation_id']})\n";
        }
    }
    echo "\n";
    
    // Execute conversion
    echo "Step 4: Execute auto-conversion\n";
    echo "───────────────────────────────────────────────────────────────────────\n\n";
    
    $itemIds = array_map(function ($item) {
        return $item['id'];
    }, $conversionCheck['items_to_remove']);
    
    echo "Removing items: [" . implode(", ", $itemIds) . "]\n";
    echo "Creating package: ID " . $conversionCheck['package_id'] . "\n\n";
    
    $updatedFiche = $receptionService->autoConvertToPackageOnAddItem(
        $fiche->id,
        $conversionCheck['package_id'],
        $itemIds,
        $newPrestationIds
    );
    
    echo "✅ Conversion executed!\n\n";
    
    // Verify results
    echo "Step 5: Verify results\n";
    echo "───────────────────────────────────────────────────────────────────────\n\n";
    
    $afterItems = ficheNavetteItem::where('fiche_navette_id', $fiche->id)->get();
    
    echo "Fiche items after conversion: " . $afterItems->count() . "\n";
    foreach ($afterItems as $item) {
        if ($item->package_id) {
            $pkg = PrestationPackage::find($item->package_id);
            echo "  ✅ Package item: {$pkg->name} (ID: {$item->package_id})\n";
        } else {
            echo "  ❌ Individual item: prestation_id={$item->prestation_id}\n";
        }
    }
    echo "\n";
    
    // Verify BOTH old items are gone
    $oldPackageCount = $afterItems->where('package_id', 8)->count();
    $oldPrestationCount = $afterItems->where('prestation_id', 5)->count();
    $newPackageCount = $afterItems->where('package_id', 11)->count();
    
    echo "Verification:\n";
    echo "  Old PACK CARDIOLOGIE 04 (ID 8): $oldPackageCount items\n";
    echo "  Old prestation 5 items: $oldPrestationCount items\n";
    echo "  New PACK CARDIOLOGIE 05 (ID 11): $newPackageCount items\n\n";
    
    if ($oldPackageCount === 0 && $oldPrestationCount === 0 && $newPackageCount === 1) {
        echo "════════════════════════════════════════════════════════════════════════\n";
        echo "✅ ADVANCED TEST SUCCESSFUL!\n";
        echo "════════════════════════════════════════════════════════════════════════\n";
        echo "\nBoth old package AND old individual items were removed!\n";
        echo "New package was created successfully!\n";
    } else {
        echo "════════════════════════════════════════════════════════════════════════\n";
        echo "❌ TEST FAILED!\n";
        echo "════════════════════════════════════════════════════════════════════════\n";
        if ($oldPackageCount > 0) {
            echo "ERROR: Old package (ID 8) still exists!\n";
        }
        if ($oldPrestationCount > 0) {
            echo "ERROR: Old individual prestation items still exist!\n";
        }
        if ($newPackageCount !== 1) {
            echo "ERROR: New package (ID 11) not created correctly!\n";
        }
        throw new Exception('Test verification failed');
    }
    
    // Rollback
    DB::rollBack();
    echo "\nTest data rolled back (not saved to database)\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n";
