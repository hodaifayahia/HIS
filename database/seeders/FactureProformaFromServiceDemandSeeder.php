<?php

namespace Database\Seeders;

use App\Models\FactureProforma;
use App\Models\ServiceDemendPurchcing;
use App\Services\Purchsing\order\ServiceDemandFournisseurService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class FactureProformaFromServiceDemandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $service = new ServiceDemandFournisseurService;

        // Select service demands that are approved and don't yet have proformas
        $demands = ServiceDemendPurchcing::with(['items.fournisseurAssignments'])
            ->where('status', 'approved')
            ->get();

        foreach ($demands as $demand) {
            // Group assignment ids by supplier
            $assignmentsBySupplier = [];

            foreach ($demand->items as $item) {
                foreach ($item->fournisseurAssignments as $assignment) {
                    // Only consider assignments that are not already received/cancelled
                    if (in_array($assignment->status, ['pending', 'assigned', 'ordered'])) {
                        $assignmentsBySupplier[$assignment->fournisseur_id][] = $assignment->id;
                    }
                }
            }

            foreach ($assignmentsBySupplier as $fournisseurId => $assignmentIds) {
                // Skip if a proforma for this demand + supplier already exists
                $exists = FactureProforma::where('service_demand_purchasing_id', $demand->id)
                    ->where('fournisseur_id', $fournisseurId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                try {
                    $service->createFactureProformaFromAssignments($demand->id, $fournisseurId, $assignmentIds);
                } catch (\Exception $e) {
                    Log::error('Seeder: failed to create facture proforma for demand '.$demand->id.' supplier '.$fournisseurId.': '.$e->getMessage());
                }
            }
        }
    }
}
