<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CONFIGURATION\Prestation;
use App\Models\Specialization;

echo "=== Simulating API Response ===\n\n";

// Simulate the API endpoint: /api/reception/prestations/by-specialization/1
$specializationId = 1; // Cardiology

$prestations = Prestation::with(['service', 'specialization', 'modalityType'])
    ->where('specialization_id', $specializationId)
    ->where('is_active', true)
    ->get()
    ->map(function ($prestation) {
        return [
            'id' => $prestation->id,
            'name' => $prestation->name,
            'internal_code' => $prestation->internal_code,
            'description' => $prestation->description,
            'price' => $prestation->public_price,
            'duration' => $prestation->default_duration_minutes,
            'service_id' => $prestation->service_id,
            'need_an_appointment' => $prestation->need_an_appointment,
            'service_name' => $prestation->service->name ?? '',
            'specialization_id' => $prestation->specialization_id,
            'specialization_name' => $prestation->specialization->name ?? '',
            'required_prestations_info' => $prestation->required_prestations_info,
            'patient_instructions' => $prestation->patient_instructions,
            'type' => 'prestation',
        ];
    });

echo "API Response:\n";
echo json_encode([
    'success' => true,
    'data' => $prestations,
], JSON_PRETTY_PRINT);

echo "\n\n=== Dependency Extraction Test ===\n\n";

// Simulate frontend logic
foreach ($prestations as $p) {
    if (! empty($p['required_prestations_info']) && is_array($p['required_prestations_info'])) {
        echo "Prestation: {$p['name']} (ID: {$p['id']})\n";
        echo '  Has dependencies: '.json_encode($p['required_prestations_info'])."\n";

        // Get dependency details
        $depIds = $p['required_prestations_info'];
        $dependencies = Prestation::whereIn('id', $depIds)->get(['id', 'name', 'public_price']);

        echo "  Dependency details:\n";
        foreach ($dependencies as $dep) {
            echo "    - [{$dep->id}] {$dep->name} ({$dep->public_price} DZD)\n";
        }
        echo "\n";
    }
}

echo "=== Test Complete ===\n";
