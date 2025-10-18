<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CONFIGURATION\Prestation;

echo "=== Adding Patient Instructions ===\n\n";

// Add patient instructions to prestations
$updates = [
    1 => "Fast for 6 hours before the ECG test. Avoid caffeine and smoking 2 hours before.",
    2 => "Bring all previous cardiology reports and test results. List current medications.",
    3 => "Fast for 4 hours before the echocardiography. Wear comfortable clothing.",
];

foreach ($updates as $id => $instructions) {
    $prestation = Prestation::find($id);
    if ($prestation) {
        $prestation->patient_instructions = $instructions;
        $prestation->save();
        echo "✓ Updated {$prestation->name}\n";
        echo "  Instructions: {$instructions}\n\n";
    }
}

echo "=== Verifying Updates ===\n\n";

$prestations = Prestation::whereIn('id', [1, 2, 3])->get(['id', 'name', 'patient_instructions', 'required_prestations_info']);

foreach ($prestations as $p) {
    echo "ID {$p->id}: {$p->name}\n";
    echo "  Patient Instructions: " . ($p->patient_instructions ?? 'None') . "\n";
    echo "  Dependencies: " . json_encode($p->required_prestations_info) . "\n\n";
}

echo "=== Complete ===\n";
