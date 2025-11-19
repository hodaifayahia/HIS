<?php

namespace App\Console\Commands;

use App\Models\FactureProforma;
use App\Models\ServiceDemendPurchcing;
use App\Services\Purchsing\order\ServiceDemandFournisseurService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateFactureProformaFromDemands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * --dry-run: show what would be created without persisting
     * --demand=ID: limit to a single service demand id
     */
    protected $signature = 'facture:create-from-demands {--dry-run} {--demand=}';

    /**
     * The console command description.
     */
    protected $description = 'Create FactureProformas from approved ServiceDemendPurchcing assignments (grouped by supplier). Supports --dry-run and --demand=ID.';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $demandId = $this->option('demand');

        $service = new ServiceDemandFournisseurService;

        $query = ServiceDemendPurchcing::with(['items.fournisseurAssignments'])
            ->where('status', 'approved');

        if ($demandId) {
            $query->where('id', $demandId);
        }

        $demands = $query->get();

        if ($demands->isEmpty()) {
            $this->info('No approved service demands found'.($demandId ? " for id {$demandId}" : ''));

            return 0;
        }

        $createdCount = 0;
        foreach ($demands as $demand) {
            $this->line("Processing demand {$demand->id} ({$demand->demand_code})");

            $assignmentsBySupplier = [];
            foreach ($demand->items as $item) {
                foreach ($item->fournisseurAssignments as $assignment) {
                    if (in_array($assignment->status, ['pending', 'assigned', 'ordered'])) {
                        $assignmentsBySupplier[$assignment->fournisseur_id][] = $assignment->id;
                    }
                }
            }

            foreach ($assignmentsBySupplier as $fournisseurId => $assignmentIds) {
                $exists = FactureProforma::where('service_demand_purchasing_id', $demand->id)
                    ->where('fournisseur_id', $fournisseurId)
                    ->exists();

                if ($exists) {
                    $this->line("  => Proforma already exists for supplier {$fournisseurId}, skipping");

                    continue;
                }

                $this->line('  => Supplier '.$fournisseurId.' assignments: '.count($assignmentIds));

                if ($dryRun) {
                    $this->line('     (dry-run) would create facture proforma for supplier '.$fournisseurId.' with '.count($assignmentIds).' assignments');

                    continue;
                }

                try {
                    $facture = $service->createFactureProformaFromAssignments($demand->id, $fournisseurId, $assignmentIds);
                    $this->info('     Created FactureProforma id='.$facture->id.' supplier='.$fournisseurId);
                    $createdCount++;
                } catch (\Exception $e) {
                    $this->error('     Failed to create proforma for supplier '.$fournisseurId.': '.$e->getMessage());
                    Log::error('Console createFactureProformaFromDemands error: '.$e->getMessage());
                }
            }
        }

        $this->info(($dryRun ? 'Dry-run complete.' : "Done. Created {$createdCount} facture proforma(s)."));

        return 0;
    }
}
