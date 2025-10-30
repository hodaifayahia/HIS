<?php

namespace App\Observers\Reception;

use App\Models\Reception\ficheNavetteItem;
use App\Models\Reception\ficheNavette;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FicheNavetteItemObserver
 * 
 * Handles automatic business logic triggers when fiche navette items are created/updated/deleted.
 * Decouples model lifecycle from service layer - makes code cleaner and more maintainable.
 */
class FicheNavetteItemObserver
{
    /**
     * Handle item creation - triggers auto-conversion check
     * Extracts the logic from addItemsToFicheNavette() to keep it DRY
     */
    public function created(ficheNavetteItem $item): void
    {
        Log::info('FicheNavetteItem created', [
            'item_id' => $item->id,
            'fiche_id' => $item->fiche_navette_id,
            'prestation_id' => $item->prestation_id,
            'package_id' => $item->package_id,
        ]);

        // Delegate to domain-specific action
        try {
            // This dispatch happens async - no blocking in the HTTP response
            dispatch(new \App\Jobs\Reception\CheckAndConvertFichePackageJob(
                $item->fiche_navette_id
            ))->onQueue('default');
        } catch (\Exception $e) {
            Log::error('Failed to dispatch package conversion job', [
                'error' => $e->getMessage(),
                'item_id' => $item->id,
            ]);
        }
    }

    /**
     * Handle item deletion - update fiche totals
     * Automatically recalculates total_amount when item is removed
     */
    public function deleted(ficheNavetteItem $item): void
    {
        Log::info('FicheNavetteItem deleted', [
            'item_id' => $item->id,
            'fiche_id' => $item->fiche_navette_id,
            'price_removed' => $item->final_price,
        ]);

        // Recalculate fiche total
        $this->recalculateFicheTotal($item->fiche_navette_id, $item->final_price);
    }

    /**
     * Handle item updates
     * Recalculates totals if price changed
     */
    public function updated(ficheNavetteItem $item): void
    {
        // Only recalculate if price changed
        if ($item->wasChanged('final_price')) {
            $oldPrice = $item->getOriginal('final_price') ?? 0;
            $priceDifference = $item->final_price - $oldPrice;
            
            Log::info('FicheNavetteItem price updated', [
                'item_id' => $item->id,
                'old_price' => $oldPrice,
                'new_price' => $item->final_price,
                'difference' => $priceDifference,
            ]);

            $this->recalculateFicheTotal($item->fiche_navette_id, -$priceDifference);
        }
    }

    /**
     * Recalculate total amount for the fiche navette
     * Encapsulates the calculation logic
     */
    private function recalculateFicheTotal(int $ficheNavetteId, float $adjustment): void
    {
        try {
            DB::transaction(function () use ($ficheNavetteId, $adjustment) {
                $fiche = ficheNavette::findOrFail($ficheNavetteId);
                $currentTotal = $fiche->total_amount ?? 0;
                $fiche->update(['total_amount' => max(0, $currentTotal + $adjustment)]);

                Log::info('Fiche total recalculated', [
                    'fiche_id' => $ficheNavetteId,
                    'previous_total' => $currentTotal,
                    'new_total' => $fiche->total_amount,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Failed to recalculate fiche total', [
                'fiche_id' => $ficheNavetteId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
