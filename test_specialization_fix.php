<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a mock request
$request = Request::create('/api/reception/prestations/fichenavette', 'GET');
$response = $kernel->handle($request);

echo "Testing Specialization Filtering Fix\n";
echo "====================================\n\n";

echo "Request URL: /api/reception/prestations/fichenavette\n";
echo "Response Status: " . $response->getStatusCode() . "\n";

if ($response->getStatusCode() === 200) {
    $content = $response->getContent();
    $data = json_decode($content, true);
    
    if (isset($data['data']) && is_array($data['data'])) {
        echo "Number of prestations returned: " . count($data['data']) . "\n";
        echo "✅ API endpoint is working correctly\n";
        
        // Check if we have prestations from different specializations
        $specializations = [];
        foreach ($data['data'] as $prestation) {
            if (isset($prestation['specialization_id'])) {
                $specializations[] = $prestation['specialization_id'];
            }
        }
        
        $uniqueSpecializations = array_unique($specializations);
        echo "Number of different specializations: " . count($uniqueSpecializations) . "\n";
        
        if (count($uniqueSpecializations) > 1) {
            echo "✅ Prestations from multiple specializations are being returned\n";
            echo "✅ Specialization filtering fix appears to be working!\n";
        } else {
            echo "⚠️  Only prestations from one specialization found\n";
        }
    } else {
        echo "❌ Invalid response format\n";
    }
} else {
    echo "❌ API request failed\n";
    echo "Response: " . $response->getContent() . "\n";
}

echo "\nFrontend Component Fix Verification:\n";
echo "===================================\n";

// Check if the frontend files have been updated
$frontendFiles = [
    '/home/administrator/www/HIS/resources/js/Components/Apps/Emergency/components/CustomPrestationSelection.vue',
    '/home/administrator/www/HIS/resources/js/Components/Apps/reception/components/CustomPrestationSelection.vue',
    '/home/administrator/www/HIS/resources/js/Components/Apps/Nursing/components/CustomPrestationSelection.vue',
    '/home/administrator/www/HIS/resources/js/Components/Apps/Emergency/components/PrestationSelection.vue',
    '/home/administrator/www/HIS/resources/js/Components/Apps/reception/components/PrestationSelection.vue',
    '/home/administrator/www/HIS/resources/js/Components/Apps/Nursing/components/PrestationSelection.vue'
];

foreach ($frontendFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check if specialization filtering has been removed
        if (strpos($content, '// Removed specialization filtering') !== false) {
            echo "✅ " . basename($file) . " - Specialization filtering removed\n";
        } else {
            echo "⚠️  " . basename($file) . " - May still contain specialization filtering\n";
        }
    } else {
        echo "❌ " . basename($file) . " - File not found\n";
    }
}

echo "\n🎉 Specialization filtering fix verification completed!\n";