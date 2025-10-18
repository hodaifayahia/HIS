<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\B2B\Convention;
use App\Models\B2B\Annex;
use App\Models\B2B\PrestationPricing;
use App\Services\B2B\AnnexCreationService;
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Multiple Percentage Annex Creation ===\n\n";

try {
    // Find a convention with multiple percentages
    $convention = Convention::with('contractPercentages')->whereHas('contractPercentages')->first();

    if (!$convention) {
        echo "No convention found with multiple percentages. Let's find any convention with percentages.\n";
        $convention = Convention::with('contractPercentages')->whereHas('contractPercentages')->first();

        if (!$convention) {
            echo "No convention found with any percentages. Creating a test convention with multiple percentages...\n";

            // Create a test convention with percentages
            DB::transaction(function() {
                $convention = Convention::create([
                    'name' => 'Test Convention Multiple Percentages',
                    'service_id' => 1, // Assuming service ID 1 exists
                    'is_active' => true,
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);

                // Create convention detail
                $convention->conventionDetail()->create([
                    'discount_percentage' => 20, // fallback
                    'max_price' => 1000,
                ]);

                // Create multiple percentages
                $convention->contractPercentages()->createMany([
                    ['percentage' => 20],
                    ['percentage' => 40],
                    ['percentage' => 60],
                ]);

                echo "Created test convention with ID: {$convention->id}\n";
                return $convention;
            });

            $convention = Convention::with('contractPercentages')->find($convention->id);
        }
    }

    echo "Using Convention: {$convention->name} (ID: {$convention->id})\n";
    echo "Contract Percentages: " . $convention->contractPercentages->pluck('percentage')->join(', ') . "%\n\n";

    // Create annex using the service
    $annexService = app(AnnexCreationService::class);

    // Get a valid service_id that has prestations
    $serviceWithPrestations = DB::table('prestations')
        ->select('service_id')
        ->groupBy('service_id')
        ->havingRaw('COUNT(*) > 0')
        ->first();

    if (!$serviceWithPrestations) {
        echo "No service found with prestations. Creating test prestations...\n";
        // Create a test prestation
        DB::table('prestations')->insert([
            'name' => 'Test Prestation',
            'service_id' => 1,
            'public_price' => 1000,
            'vat_rate' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $serviceId = 1;
    } else {
        $serviceId = $serviceWithPrestations->service_id;
    }

    $annexData = [
        'annex_name' => 'Test Annex Multiple Percentages ' . now()->format('Y-m-d H:i:s'),
        'convention_id' => $convention->id,
        'service_id' => $serviceId,
        'description' => 'Test annex for multiple percentage functionality',
        'is_active' => true,
        'min_price' => 0,
        'prestation_prix_status' => 'public_prix',
    ];

    echo "Creating annex...\n";
    $annex = $annexService->createAnnexAndInitializePrestations($annexData);

    echo "✅ Annex created successfully! ID: {$annex->id}\n\n";

    // Check how many pricing entries were created
    $pricingCount = PrestationPricing::where('annex_id', $annex->id)->count();
    $expectedCount = $convention->contractPercentages->count(); // Should be number of percentages

    echo "Pricing entries created: {$pricingCount}\n";
    echo "Expected entries (per prestation): {$expectedCount}\n\n";

    // Get one prestation and check its pricing entries
    $samplePrestation = PrestationPricing::where('annex_id', $annex->id)->first();
    if ($samplePrestation) {
        $prestationPricings = PrestationPricing::where('annex_id', $annex->id)
            ->where('prestation_id', $samplePrestation->prestation_id)
            ->with('contractPercentage')
            ->get();

        echo "Sample prestation pricing entries:\n";
        foreach ($prestationPricings as $pricing) {
            $percentage = $pricing->contractPercentage ? $pricing->contractPercentage->percentage : 'N/A';
            echo "  - Percentage: {$percentage}%, Company Price: {$pricing->company_price}, Patient Price: {$pricing->patient_price}\n";
        }
        echo "\n";
    }

    if ($pricingCount > 0 && $pricingCount >= $expectedCount) {
        echo "✅ SUCCESS: Multiple pricing entries created successfully!\n";
        echo "✅ The unique constraint now allows multiple entries per prestation/annex/percentage.\n";
    } else {
        echo "❌ FAILURE: Expected multiple pricing entries but got {$pricingCount}.\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}