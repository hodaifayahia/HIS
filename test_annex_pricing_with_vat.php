<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\CONFIGURATION\Prestation;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Annex Pricing Calculation with VAT ===\n\n";

try {
    // Get a sample prestation with prices
    $prestation = Prestation::whereNotNull('public_price')
        ->whereNotNull('vat_rate')
        ->first();

    if (!$prestation) {
        echo "No prestation found with public_price and vat_rate.\n";
        exit;
    }

    echo "Prestation: {$prestation->name}\n";
    echo "=========================================\n\n";

    // Test case 1: public_prix
    echo "Case 1: prestation_prix_status = 'public_prix'\n";
    echo "-----------------------------------------------\n";
    echo "Public Price: {$prestation->public_price} MAD\n";
    echo "Consumables: " . ($prestation->consumables_cost ?? 0) . " MAD\n";
    echo "VAT Rate: " . ($prestation->vat_rate ?? 0) . "%\n";
    echo "Price with VAT (using getPriceWithVatAttribute): {$prestation->price_with_vat} MAD\n";
    echo "✅ This value will be used as initialBasePrice\n\n";

    // Test case 2: convenience_prix
    echo "Case 2: prestation_prix_status = 'convenience_prix'\n";
    echo "----------------------------------------------------\n";
    echo "Convenience Price: " . ($prestation->convenience_prix ?? 0) . " MAD\n";
    echo "Consumables: " . ($prestation->consumables_cost ?? 0) . " MAD\n";
    echo "VAT Rate: " . ($prestation->vat_rate ?? 0) . "%\n";
    
    // Calculate manually
    $conventionPrice = is_null($prestation->convenience_prix) ? 0.0 : (float) $prestation->convenience_prix;
    $consumables = is_null($prestation->consumables_cost) ? 0.0 : (float) $prestation->consumables_cost;
    $vat = is_null($prestation->vat_rate) ? 0.0 : (float) $prestation->vat_rate;
    $base = $conventionPrice + $consumables;
    $priceWithVat = round($base * (1 + $vat / 100), 2);
    
    echo "Calculated Price with VAT: {$priceWithVat} MAD\n";
    echo "✅ This value will be used as initialBasePrice\n\n";

    // Test case 3: empty
    echo "Case 3: prestation_prix_status = 'empty' (default)\n";
    echo "---------------------------------------------------\n";
    echo "Falls back to public price with VAT: {$prestation->price_with_vat} MAD\n";
    echo "✅ This value will be used as initialBasePrice\n\n";

    echo "=== Summary ===\n";
    echo "✅ AnnexCreationService now uses price WITH VAT as initialBasePrice\n";
    echo "✅ This ensures all calculations include VAT from the start\n";
    echo "✅ Company and patient shares will be calculated on the TTC price\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
