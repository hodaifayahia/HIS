<?php

namespace App\Jobs\Reception;

use App\Actions\Reception\PreparePackageConversionData;
use App\Actions\Reception\ExecutePackageConversion;
use App\Models\Reception\ficheNavette;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * CheckAndConvertFichePackageJob
 * 
 * Queued job that runs async after items are added to a fiche.
 * Checks if items match a package and performs conversion if applicable.
 * 
 * Benefits of async processing:
 * - HTTP response not blocked by DB queries and conversion logic
 * - Retry logic built-in if conversion fails
 * - Ability to log and monitor conversions
 */
class CheckAndConvertFichePackageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $ficheNavetteId
    ) {}

    public function handle(
        PreparePackageConversionData $preparer,
        ExecutePackageConversion $executor
    ): void {
        try {
            Log::info('🔍 Running CheckAndConvertFichePackageJob', ['fiche_id' => $this->ficheNavetteId]);

            $fiche = ficheNavette::findOrFail($this->ficheNavetteId);

            // Get all current prestations in fiche
            $existingPrestationIds = $fiche->items()
                ->whereNotNull('prestation_id')
                ->whereNull('package_id')
                ->pluck('prestation_id')
                ->toArray();

            // Prepare conversion
            $conversionData = $preparer->execute(
                $this->ficheNavetteId,
                [], // No new prestations (job is async, already added)
                $existingPrestationIds
            );

            if (!$conversionData['should_convert']) {
                Log::info('No conversion needed for fiche', [
                    'fiche_id' => $this->ficheNavetteId,
                    'reason' => $conversionData['message'],
                ]);
                return;
            }

            // Execute conversion
            $executor->execute(
                $this->ficheNavetteId,
                $conversionData['package_id'],
                array_map(fn($item) => $item['id'], $conversionData['items_to_remove']),
            );

            Log::info('✅ Package conversion completed', [
                'fiche_id' => $this->ficheNavetteId,
                'package_id' => $conversionData['package_id'],
            ]);

        } catch (\Exception $e) {
            Log::error('Package conversion job failed', [
                'fiche_id' => $this->ficheNavetteId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Let the queue retry mechanism handle retries
            throw $e;
        }
    }

    /**
     * Define retry behavior
     */
    public function tries(): int
    {
        return 3;
    }

    /**
     * Calculate backoff seconds
     */
    public function backoff(): array
    {
        return [1, 5, 15]; // Retry after 1s, 5s, 15s
    }
}
