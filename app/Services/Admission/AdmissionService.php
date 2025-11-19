<?php

namespace App\Services\Admission;

use App\Models\Admission;
use App\Models\AdmissionDischargeTicket;
use App\Models\Reception\ficheNavette;
use App\Models\Reception\ficheNavetteItem;
use App\Services\Reception\FicheNavetteSearchService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdmissionService
{
    protected $ficheNavetteSearchService;

    public function __construct(FicheNavetteSearchService $ficheNavetteSearchService)
    {
        $this->ficheNavetteSearchService = $ficheNavetteSearchService;
    }

    /**
     * Create a new admission
     * Automatically handles fiche navette creation/linking and initial prestation setup
     */
    public function createAdmission(array $data): Admission
    {
        return DB::transaction(function () use ($data) {
            $patientId = $data['patient_id'];
            $admissionType = $data['type'] ?? 'nursing';

            // For surgery type, ensure initial prestation is provided
            if ($admissionType === 'surgery' && empty($data['initial_prestation_id'])) {
                throw new \Exception('Initial prestation is required for surgery admission');
            }

            // Get or create today's fiche navette for the patient
            $ficheNavette = $this->ficheNavetteSearchService->getOrCreateFicheNavetteForToday(
                $patientId,
                $admissionType
            );

            // Add initial prestation as item if admission type is 'surgery'
            if ($admissionType === 'surgery' && ! empty($data['initial_prestation_id'])) {
                $this->addInitialPrestationToFiche($ficheNavette, $data['initial_prestation_id']);
            }

            // Create admission record
            $admission = Admission::create([
                'patient_id' => $patientId,
                'doctor_id' => $data['doctor_id'] ?? null,
                'type' => $admissionType,
                'status' => 'admitted',
                'admitted_at' => now(),
                'initial_prestation_id' => $data['initial_prestation_id'] ?? null,
                'fiche_navette_id' => $ficheNavette->id,
                'documents_verified' => false,
                'created_by' => Auth::id(),
            ]);

            return $admission;
        });
    }

    /**
     * Update an admission
     */
    public function updateAdmission(int $id, array $data): Admission
    {
        return DB::transaction(function () use ($id, $data) {
            $admission = Admission::findOrFail($id);

            // Cannot update discharged admission
            if ($admission->status === 'ready_for_discharge' && $admission->discharged_at) {
                throw new \Exception('Cannot update discharged admission');
            }

            $admission->update(array_filter($data, function ($value) {
                return $value !== null;
            }));

            return $admission->fresh();
        });
    }

    /**
     * Discharge a patient
     */
    public function dischargePatient(int $id): Admission
    {
        return DB::transaction(function () use ($id) {
            $admission = Admission::with(['procedures', 'billingRecords'])->findOrFail($id);

            // Validate discharge conditions
            if (! $admission->documents_verified) {
                throw new \Exception('Cannot discharge: Documents not verified');
            }

            // Check if all procedures are completed or cancelled
            $pendingProcedures = $admission->procedures()
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count();

            if ($pendingProcedures > 0) {
                throw new \Exception('Cannot discharge: Some procedures are still pending');
            }

            // Update admission
            $admission->update([
                'status' => 'ready_for_discharge',
                'discharged_at' => now(),
            ]);

            // Generate discharge ticket
            $this->generateDischargeTicket($admission);

            return $admission->fresh();
        });
    }

    /**
     * Generate discharge ticket (auto-generated)
     */
    protected function generateDischargeTicket(Admission $admission): AdmissionDischargeTicket
    {
        return AdmissionDischargeTicket::create([
            'admission_id' => $admission->id,
            'ticket_number' => 'DT-'.$admission->id.'-'.time(),
            'authorized_by' => $admission->doctor_id,
            'generated_at' => now(),
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Get admission statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_admissions' => Admission::count(),
            'active_admissions' => Admission::whereIn('status', ['admitted', 'in_service', 'document_pending'])->count(),
            'surgery_admissions' => Admission::where('type', 'surgery')->count(),
            'nursing_admissions' => Admission::where('type', 'nursing')->count(),
            'ready_for_discharge' => Admission::where('status', 'ready_for_discharge')->count(),
            'today_admissions' => Admission::whereDate('admitted_at', today())->count(),
            'today_discharges' => Admission::whereDate('discharged_at', today())->count(),
        ];
    }

    /**
     * Add initial prestation to fiche navette
     */
    protected function addInitialPrestationToFiche(ficheNavette $ficheNavette, int $prestationId): ficheNavetteItem
    {
        // Check if this prestation is already in the fiche
        $existingItem = $ficheNavette->items()
            ->where('prestation_id', $prestationId)
            ->whereNull('package_id')
            ->first();

        if ($existingItem) {
            return $existingItem;
        }

        // Get prestation details
        $prestation = \App\Models\CONFIGURATION\Prestation::findOrFail($prestationId);

        // Get the price with VAT and consumables variant
        $price = $prestation->price_with_vat_and_consumables_variant ?? 0;

        // Handle both array and scalar returns from the accessor
        if (is_array($price)) {
            $price = $price['ttc_with_consumables_vat'] ?? $price['ttc'] ?? 0;
        }
        $price = (float) $price;

        // Create new fiche item with all required fields from ficheNavetteItem model
        $item = ficheNavetteItem::create([
            'fiche_navette_id' => $ficheNavette->id,
            'prestation_id' => $prestationId,
            'patient_id' => $ficheNavette->patient_id,
            'base_price' => $price,
            'final_price' => $price,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        // Update fiche total amount by summing final_price
        $ficheNavette->update([
            'total_amount' => $ficheNavette->items()->sum('final_price'),
        ]);

        return $item;
    }
}
