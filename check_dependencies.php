<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CONFIGURATION\Prestation;

echo "=== Checking Prestation Dependencies ===\n\n";

// Get prestations with non-null required_prestations_info
$prestations = Prestation::where('is_active', true)
    ->get(['id', 'name', 'required_prestations_info']);

$withDependencies = 0;
$withoutDependencies = 0;

foreach ($prestations as $p) {
    $deps = $p->required_prestations_info;
    
    if ($deps && is_array($deps) && count($deps) > 0) {
        $withDependencies++;
        echo "✓ ID {$p->id}: {$p->name}\n";
        echo "  Dependencies: " . json_encode($deps) . "\n";
        
        // Get dependency names
        $depPrestations = Prestation::whereIn('id', $deps)->get(['id', 'name']);
        foreach ($depPrestations as $dep) {
            echo "    - [{$dep->id}] {$dep->name}\n";
        }
        echo "\n";
    } else {
        $withoutDependencies++;
    }
}

echo "\n=== Summary ===\n";
echo "Prestations WITH dependencies: $withDependencies\n";
echo "Prestations WITHOUT dependencies: $withoutDependencies\n";
echo "Total: " . ($withDependencies + $withoutDependencies) . "\n";

// Now let's manually add some dependencies for testing
echo "\n=== Setting Test Dependencies ===\n";

// Set ECG (ID: 1) to require CONSULTATION CARDIOLOGIE (ID: 2)
$ecg = Prestation::find(1);
if ($ecg) {
    echo "Before: ECG dependencies = " . json_encode($ecg->required_prestations_info) . "\n";
    $ecg->required_prestations_info = [2, 3];
    $ecg->save();
    echo "After: ECG dependencies = " . json_encode($ecg->fresh()->required_prestations_info) . "\n";
}

// Set CONSULTATION CARDIOLOGIE (ID: 2) to require ECHOCARDIOGRAPHIE (ID: 3)
$consultation = Prestation::find(2);
if ($consultation) {
    echo "\nBefore: CONSULTATION dependencies = " . json_encode($consultation->required_prestations_info) . "\n";
    $consultation->required_prestations_info = [3];
    $consultation->save();
    echo "After: CONSULTATION dependencies = " . json_encode($consultation->fresh()->required_prestations_info) . "\n";
}

echo "\n=== Test Complete ===\n";
