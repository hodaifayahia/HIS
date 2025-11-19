<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CONFIGURATION\Prestation;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║          DEPENDENCY SYSTEM - COMPLETE VERIFICATION             ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Test 1: Verify API response structure
echo "TEST 1: API Response Structure\n";
echo "================================\n";

$specializationId = 1;
$prestations = Prestation::with(['service', 'specialization'])
    ->where('specialization_id', $specializationId)
    ->where('is_active', true)
    ->get()
    ->map(function ($prestation) {
        return [
            'id' => $prestation->id,
            'name' => $prestation->name,
            'price' => $prestation->public_price,
            'required_prestations_info' => $prestation->required_prestations_info,
            'patient_instructions' => $prestation->patient_instructions,
        ];
    });

foreach ($prestations as $p) {
    echo "✓ {$p['name']} (ID: {$p['id']})\n";
    echo "  Dependencies: " . json_encode($p['required_prestations_info']) . "\n";
    echo "  Instructions: " . ($p['patient_instructions'] ? 'Yes' : 'No') . "\n\n";
}

// Test 2: Simulate user selecting ECG (which has dependencies)
echo "\nTEST 2: User Selects ECG (has dependencies [2, 3])\n";
echo "====================================================\n";

$selectedPrestationIds = [1]; // User selects ECG
echo "User selected: " . json_encode($selectedPrestationIds) . "\n\n";

$allDependencyIds = new Set();
$combinedInstructions = [];

class Set {
    private $items = [];
    
    public function add($item) {
        if (!in_array($item, $this->items)) {
            $this->items[] = $item;
        }
    }
    
    public function size() {
        return count($this->items);
    }
    
    public function toArray() {
        return $this->items;
    }
}

$allDependencyIds = new Set();
$combinedInstructions = [];

foreach ($selectedPrestationIds as $prestationId) {
    $prestation = $prestations->firstWhere('id', $prestationId);
    
    if ($prestation) {
        echo "Processing: {$prestation['name']}\n";
        
        // Add patient instructions
        if ($prestation['patient_instructions']) {
            $combinedInstructions[] = "{$prestation['name']}: {$prestation['patient_instructions']}";
            echo "  ✓ Added instructions\n";
        }
        
        // Add dependencies
        if ($prestation['required_prestations_info'] && is_array($prestation['required_prestations_info'])) {
            foreach ($prestation['required_prestations_info'] as $depId) {
                if ($depId && !in_array($depId, $selectedPrestationIds)) {
                    $allDependencyIds->add($depId);
                }
            }
            echo "  ✓ Found {$allDependencyIds->size()} dependencies\n";
        }
    }
}

echo "\nDependencies to show user:\n";
$dependencyIds = $allDependencyIds->toArray();
$dependencyPrestations = Prestation::whereIn('id', $dependencyIds)
    ->get(['id', 'name', 'public_price', 'patient_instructions']);

foreach ($dependencyPrestations as $dep) {
    echo "  ☐ {$dep->name} ({$dep->public_price} DZD)\n";
}

echo "\nPatient Instructions:\n";
echo "─────────────────────\n";
echo implode("\n\n", $combinedInstructions);
echo "\n\n";

// Test 3: User selects some dependencies
echo "\nTEST 3: User Selects Dependency (ID: 3 - ECHOCARDIOGRAPHIE)\n";
echo "============================================================\n";

$selectedDependencies = [3];
echo "User selected dependencies: " . json_encode($selectedDependencies) . "\n";

$allPrestations = array_merge($selectedPrestationIds, $selectedDependencies);
$uniquePrestations = array_unique($allPrestations);

echo "Combined prestations to submit: " . json_encode($uniquePrestations) . "\n\n";

// Test 4: Check for package match
echo "\nTEST 4: Package Detection\n";
echo "==========================\n";

use App\Models\CONFIGURATION\PrestationPackage;

sort($uniquePrestations);
echo "Sorted prestations: " . json_encode($uniquePrestations) . "\n";

$activePackages = PrestationPackage::where('is_active', true)->with('items')->get();
$matchedPackage = null;

foreach ($activePackages as $package) {
    $packagePrestationIds = $package->items->pluck('prestation_id')->toArray();
    sort($packagePrestationIds);
    
    echo "\nChecking package: {$package->name}\n";
    echo "  Package has: " . json_encode($packagePrestationIds) . "\n";
    echo "  User has: " . json_encode($uniquePrestations) . "\n";
    
    if ($uniquePrestations === $packagePrestationIds) {
        $matchedPackage = $package;
        echo "  ✓ MATCH!\n";
        break;
    } else {
        echo "  ✗ No match\n";
    }
}

if ($matchedPackage) {
    echo "\n✓ Will store as package: {$matchedPackage->name} (ID: {$matchedPackage->id})\n";
    echo "  Package price: {$matchedPackage->price} DZD\n";
} else {
    echo "\n✗ No package match - will store individual prestations\n";
    
    $individualTotal = 0;
    foreach ($uniquePrestations as $pid) {
        $p = Prestation::find($pid);
        if ($p) {
            echo "  - {$p->name}: {$p->public_price} DZD\n";
            $individualTotal += (float) $p->public_price;
        }
    }
    echo "  Total: {$individualTotal} DZD\n";
}

// Test 5: Full workflow simulation
echo "\n\nTEST 5: Complete Workflow\n";
echo "==========================\n";

$scenarios = [
    'ECG only' => [1],
    'ECG + ECHOCARDIOGRAPHIE' => [1, 3],
    'ECG + CONSULTATION + ECHO (Package!)' => [1, 2, 3],
    'CONSULTATION + ECHO' => [2, 3],
];

foreach ($scenarios as $name => $prestationIds) {
    echo "\nScenario: {$name}\n";
    echo "  Selected: " . json_encode($prestationIds) . "\n";
    
    sort($prestationIds);
    $matched = false;
    
    foreach ($activePackages as $pkg) {
        $pkgIds = $pkg->items->pluck('prestation_id')->toArray();
        sort($pkgIds);
        
        if ($prestationIds === $pkgIds) {
            echo "  ✓ Package: {$pkg->name} ({$pkg->price} DZD)\n";
            $matched = true;
            break;
        }
    }
    
    if (!$matched) {
        $total = 0;
        foreach ($prestationIds as $pid) {
            $p = Prestation::find($pid);
            if ($p) {
                $total += (float) $p->public_price;
            }
        }
        echo "  ✗ Individual ({$total} DZD)\n";
    }
}

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    ALL TESTS COMPLETE                          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
