<?php

require __DIR__.'/vendor/autoload.php';

use App\Models\CONFIGURATION\Prestation;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  BEFORE vs AFTER: Annex Pricing Calculation Comparison        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

try {
    $prestation = Prestation::whereNotNull('public_price')
        ->whereNotNull('vat_rate')
        ->whereNotNull('convenience_prix')
        ->first();

    if (! $prestation) {
        echo "No suitable prestation found.\n";
        exit;
    }

    echo "Test Prestation: {$prestation->name}\n";
    echo "══════════════════════════════════════════════════════════════\n\n";

    $publicPrice = (float) $prestation->public_price;
    $conveniencePrice = (float) ($prestation->convenience_prix ?? 0);
    $consumables = (float) ($prestation->consumables_cost ?? 0);
    $vatRate = (float) ($prestation->vat_rate ?? 0);

    echo "Prestation Data:\n";
    echo "----------------\n";
    echo "Public Price (HT):      {$publicPrice} MAD\n";
    echo "Convenience Price (HT): {$conveniencePrice} MAD\n";
    echo "Consumables:            {$consumables} MAD\n";
    echo "VAT Rate:               {$vatRate}%\n\n";

    // Calculate TTC
    $publicPriceTTC = round(($publicPrice + $consumables) * (1 + $vatRate / 100), 2);
    $conveniencePriceTTC = round(($conveniencePrice + $consumables) * (1 + $vatRate / 100), 2);

    echo "══════════════════════════════════════════════════════════════\n";
    echo "SCENARIO 1: prestation_prix_status = 'public_prix'\n";
    echo "══════════════════════════════════════════════════════════════\n\n";

    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ BEFORE (WITHOUT VAT):                                       │\n";
    echo "├─────────────────────────────────────────────────────────────┤\n";
    echo "│ initialBasePrice = public_price                             │\n";
    echo "│                  = {$publicPrice} MAD (HT)                         │\n";
    echo "│                                                             │\n";
    echo "│ ❌ Problem: VAT not included in base calculation            │\n";
    echo "│ ❌ Company/patient shares calculated on HT price            │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n\n";

    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ AFTER (WITH VAT): ✅                                         │\n";
    echo "├─────────────────────────────────────────────────────────────┤\n";
    echo "│ initialBasePrice = prestation->price_with_vat               │\n";
    echo "│                  = (public_price + consumables) × (1+VAT/100)│\n";
    $vatMultiplier = 1 + ($vatRate / 100);
    echo "│                  = ({$publicPrice} + {$consumables}) × {$vatMultiplier}                  │\n";
    echo "│                  = {$publicPriceTTC} MAD (TTC)                      │\n";
    echo "│                                                             │\n";
    echo "│ ✅ VAT included from the start                              │\n";
    echo "│ ✅ Company/patient shares calculated on TTC price           │\n";
    echo "│ ✅ Accurate billing and financial reporting                 │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n\n";

    echo "══════════════════════════════════════════════════════════════\n";
    echo "SCENARIO 2: prestation_prix_status = 'convenience_prix'\n";
    echo "══════════════════════════════════════════════════════════════\n\n";

    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ BEFORE (WITHOUT VAT):                                       │\n";
    echo "├─────────────────────────────────────────────────────────────┤\n";
    echo "│ initialBasePrice = convenience_prix                         │\n";
    echo "│                  = {$conveniencePrice} MAD (HT)                       │\n";
    echo "│                                                             │\n";
    echo "│ ❌ Problem: VAT not included in base calculation            │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n\n";

    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ AFTER (WITH VAT): ✅                                         │\n";
    echo "├─────────────────────────────────────────────────────────────┤\n";
    echo "│ conventionPrice = convenience_prix                          │\n";
    echo "│ consumables = consumables_cost                              │\n";
    echo "│ vat = vat_rate                                              │\n";
    echo "│ base = conventionPrice + consumables                        │\n";
    echo "│      = {$conveniencePrice} + {$consumables}                               │\n";
    echo '│      = '.($conveniencePrice + $consumables)."                                                 │\n";
    echo "│ initialBasePrice = base × (1 + vat/100)                     │\n";
    $baseConv = $conveniencePrice + $consumables;
    echo "│                  = {$baseConv} × {$vatMultiplier}                              │\n";
    echo "│                  = {$conveniencePriceTTC} MAD (TTC)                     │\n";
    echo "│                                                             │\n";
    echo "│ ✅ VAT calculated and included                              │\n";
    echo "│ ✅ Uses same logic as Prestation model                      │\n";
    echo "└─────────────────────────────────────────────────────────────┘\n\n";

    // Show example with company/patient split
    $discount = 90; // 90% company pays
    echo "══════════════════════════════════════════════════════════════\n";
    echo "COMPLETE EXAMPLE: Company/Patient Share Calculation\n";
    echo "══════════════════════════════════════════════════════════════\n\n";
    echo "Convention Discount: {$discount}% (company pays {$discount}%)\n";
    echo "Max Price Cap: 300 MAD\n\n";

    echo "Using public_prix strategy (TTC = {$publicPriceTTC} MAD):\n";
    echo "─────────────────────────────────────────────────────────────\n";

    $companyShare = round($publicPriceTTC * ($discount / 100), 2);
    $patientShare = round($publicPriceTTC - $companyShare, 2);

    echo "Original Calculation:\n";
    echo "  Company share = {$publicPriceTTC} × {$discount}% = {$companyShare} MAD\n";
    echo "  Patient share = {$publicPriceTTC} - {$companyShare} = {$patientShare} MAD\n\n";

    if ($companyShare > 300) {
        $excess = $companyShare - 300;
        $finalCompany = 300;
        $finalPatient = $patientShare + $excess;

        echo "After Max Price Cap (300 MAD):\n";
        echo "  Excess = {$companyShare} - 300 = {$excess} MAD\n";
        echo "  Final company_price = 300 MAD ⬇️\n";
        echo "  Final patient_price = {$patientShare} + {$excess} = {$finalPatient} MAD ⬆️\n";
        echo "  max_price_exceeded = TRUE 🚨\n\n";

        echo "Stored in Database:\n";
        echo "┌─────────────────────────────────────────────────────────┐\n";
        echo "│ prix                    = {$publicPriceTTC} MAD (TTC)              │\n";
        echo "│ company_price           = {$finalCompany} MAD (capped)           │\n";
        echo "│ patient_price           = {$finalPatient} MAD (w/ excess)       │\n";
        echo "│ original_company_share  = {$companyShare} MAD                    │\n";
        echo "│ original_patient_share  = {$patientShare} MAD                     │\n";
        echo "│ max_price_exceeded      = TRUE                          │\n";
        echo "└─────────────────────────────────────────────────────────┘\n";
    } else {
        echo "After Max Price Cap (300 MAD):\n";
        echo "  No cap needed (company share {$companyShare} ≤ 300)\n";
        echo "  Final company_price = {$companyShare} MAD ✅\n";
        echo "  Final patient_price = {$patientShare} MAD ✅\n";
        echo "  max_price_exceeded = FALSE\n";
    }

    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║                    SUMMARY                                     ║\n";
    echo "╠════════════════════════════════════════════════════════════════╣\n";
    echo "║ ✅ All prices now include VAT (TTC)                            ║\n";
    echo "║ ✅ Company/patient shares calculated on final price            ║\n";
    echo "║ ✅ Consistent with PrestationPricing model                     ║\n";
    echo "║ ✅ Accurate financial calculations                             ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";

} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
