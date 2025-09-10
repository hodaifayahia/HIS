<?php

namespace App\Services\CONFIGURATION;


use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\PrestationMedication;
use App\Models\CONFIGURATION\Service;
use App\Models\Specialization;
use App\Models\CONFIGURATION\ModalityType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CONFIGURATION\PrestationRequest;

class PrestationService
{
    /**
     * Get paginated prestations with filters
     */
    public function getPrestations(Request $request)
    {
        $query = Prestation::with(['service', 'specialization', 'modalityType']);

        // Add search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('internal_code', 'like', "%{$search}%")
                  ->orWhere('billing_code', 'like', "%{$search}%");
            });
        }

        // Add filtering by service
        if ($request->has('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        // Add filtering by type
        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        // Add filtering by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }


        return $query->get();
    }

    /**
     * Create a new prestation
     */
    public function createPrestation(PrestationRequest $request)
    {
        DB::beginTransaction();

        try {
            $prestationData = $this->preparePrestationData($request);
            $prestation = Prestation::create($prestationData);

            DB::commit();
            
            return $prestation->load(['service', 'specialization', 'modalityType']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update a prestation
     */
    public function updatePrestation(PrestationRequest $request, $id)
    {
        $prestation = Prestation::findOrFail($id);

        DB::beginTransaction();

        try {
            $prestationData = $this->preparePrestationData($request);
            $prestation->update($prestationData);

            // Handle medications update if they exist in the request
            if ($request->has('medications')) {
                $this->updatePrestationMedications($prestation, $request->medications);
            }

            DB::commit();

            return $prestation->fresh(['service', 'specialization', 'modalityType', 'medications.medication']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a prestation
     */
    public function deletePrestation($id)
    {
        $prestation = Prestation::findOrFail($id);
        return $prestation->delete();
    }

    /**
     * Toggle prestation status
     */
    public function togglePrestationStatus($id)
    {
        $prestation = Prestation::findOrFail($id);
        $prestation->is_active = !$prestation->is_active;
        $prestation->save();
        
        return $prestation;
    }

    /**
     * Get filter options for forms
     */
    public function getFilterOptions()
    {
        return [
            'services' => Service::select('id', 'name')->orderBy('name')->get(),
            'specializations' => Specialization::select('id', 'name')->orderBy('name')->get(),
            'modality_types' => ModalityType::select('id', 'name')->orderBy('name')->get(),
            'payment_types' => [
                ['value' => 'cash', 'label' => 'Cash'],
                ['value' => 'card', 'label' => 'Card'],
                ['value' => 'insurance', 'label' => 'Insurance'],
                ['value' => 'bank_transfer', 'label' => 'Bank Transfer'],
            ]
        ];
    }

    /**
     * Get prestation statistics
     */
    public function getStatistics()
    {
        return [
            'total_prestations' => Prestation::count(),
            'active_prestations' => Prestation::where('is_active', true)->count(),
            'medical_prestations' => Prestation::where('type', 'Médical')->count(),
            'surgical_prestations' => Prestation::where('type', 'Chirurgical')->count(),
            'average_price' => Prestation::avg('public_price'),
        ];
    }

    /**
     * Prepare prestation data for storage
     */
    private function preparePrestationData(PrestationRequest $request)
    {
        $data = $request->validated();

        // Handle JSON fields
        $jsonFields = ['non_applicable_discount_rules', 'required_prestations_info', 'required_consents'];
        foreach ($jsonFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = is_array($data[$field]) ? json_encode($data[$field]) : $data[$field];
            }
        }

        // Handle night tariff
        if (!$request->has('night_tariff') || $request->night_tariff === null) {
            $data['night_tariff'] = null;
        }

        // Handle hospitalization nights
        if (!$request->requires_hospitalization) {
            $data['default_hosp_nights'] = null;
        }

        return $data;
    }

    /**
     * Update prestation medications
     */
    private function updatePrestationMedications($prestation, $medications)
    {
        $currentMedicationIds = $prestation->medications->pluck('id')->toArray();
        $incomingMedicationIds = [];

        foreach ($medications as $medicationData) {
            if (isset($medicationData['id'])) {
                // Update existing medication
                $medication = PrestationMedication::find($medicationData['id']);
                if ($medication && $medication->prestation_id === $prestation->id) {
                    $medication->update($this->prepareMedicationData($medicationData));
                    $incomingMedicationIds[] = $medicationData['id'];
                }
            } else {
                // Create new medication
                $newMedication = $prestation->medications()->create($this->prepareMedicationData($medicationData));
                $incomingMedicationIds[] = $newMedication->id;
            }
        }

        // Delete medications that are no longer in the incoming list
        $medicationsToDelete = array_diff($currentMedicationIds, $incomingMedicationIds);
        if (!empty($medicationsToDelete)) {
            PrestationMedication::whereIn('id', $medicationsToDelete)->delete();
        }
    }

    /**
     * Prepare medication data
     */
    private function prepareMedicationData($medicationData)
    {
        return [
            'medication_id' => $medicationData['medication_id'],
            'form' => $medicationData['form'],
            'num_times' => $medicationData['num_times'],
            'frequency' => $medicationData['frequency'],
            'start_date' => $medicationData['start_date'] ?? null,
            'end_date' => $medicationData['end_date'] ?? null,
            'period_intakes' => $medicationData['period_intakes'] ?? null,
            'timing_preference' => $medicationData['timing_preference'] ?? null,
            'frequency_period' => $medicationData['frequency_period'] ?? null,
            'description' => $medicationData['description'] ?? null,
            'pills_matin' => $medicationData['pills_matin'] ?? null,
            'pills_apres_midi' => $medicationData['pills_apres_midi'] ?? null,
            'pills_midi' => $medicationData['pills_midi'] ?? null,
            'pills_soir' => $medicationData['pills_soir'] ?? null
        ];
    }
}
