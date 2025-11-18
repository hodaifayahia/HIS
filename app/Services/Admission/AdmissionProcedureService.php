<?php

namespace App\Services\Admission;

use App\Models\AdmissionBillingRecord;
use App\Models\AdmissionProcedure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdmissionProcedureService
{
    /**
     * Create a new procedure
     */
    public function createProcedure(array $data): AdmissionProcedure
    {
        return DB::transaction(function () use ($data) {
            $procedure = AdmissionProcedure::create([
                'admission_id' => $data['admission_id'],
                'prestation_id' => $data['prestation_id'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => 'scheduled',
                'is_medication_conversion' => $data['is_medication_conversion'] ?? false,
                'performed_by' => $data['performed_by'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'created_by' => Auth::id(),
            ]);

            return $procedure;
        });
    }

    /**
     * Update a procedure
     */
    public function updateProcedure(int $id, array $data): AdmissionProcedure
    {
        return DB::transaction(function () use ($id, $data) {
            $procedure = AdmissionProcedure::findOrFail($id);

            // Cannot update completed or cancelled procedures
            if (in_array($procedure->status, ['completed', 'cancelled'])) {
                throw new \Exception('Cannot update completed or cancelled procedure');
            }

            $procedure->update(array_filter($data, function ($value) {
                return $value !== null;
            }));

            return $procedure->fresh();
        });
    }

    /**
     * Complete a procedure
     */
    public function completeProcedure(int $id): AdmissionProcedure
    {
        return DB::transaction(function () use ($id) {
            $procedure = AdmissionProcedure::with('prestation')->findOrFail($id);

            if ($procedure->status === 'completed') {
                throw new \Exception('Procedure already completed');
            }

            if ($procedure->status === 'cancelled') {
                throw new \Exception('Cannot complete cancelled procedure');
            }

            $procedure->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Create billing record if prestation exists
            if ($procedure->prestation_id && $procedure->prestation) {
                $this->createBillingRecord($procedure);
            }

            return $procedure->fresh();
        });
    }

    /**
     * Cancel a procedure
     */
    public function cancelProcedure(int $id): AdmissionProcedure
    {
        return DB::transaction(function () use ($id) {
            $procedure = AdmissionProcedure::findOrFail($id);

            if ($procedure->status === 'completed') {
                throw new \Exception('Cannot cancel completed procedure');
            }

            if ($procedure->status === 'cancelled') {
                throw new \Exception('Procedure already cancelled');
            }

            $procedure->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            return $procedure->fresh();
        });
    }

    /**
     * Create billing record for completed procedure
     */
    protected function createBillingRecord(AdmissionProcedure $procedure): void
    {
        // Get price from prestation (simplified - actual pricing may be more complex)
        $amount = $procedure->prestation->price ?? 0;

        AdmissionBillingRecord::create([
            'admission_id' => $procedure->admission_id,
            'procedure_id' => $procedure->id,
            'item_type' => 'procedure',
            'description' => $procedure->name,
            'amount' => $amount,
            'is_paid' => false,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Check medication conversion rule (> 5000 DA)
     */
    public function checkMedicationConversion(int $admissionId, float $medicationTotal): ?AdmissionProcedure
    {
        if ($medicationTotal <= 5000) {
            return null;
        }

        return $this->createProcedure([
            'admission_id' => $admissionId,
            'name' => 'Medication Charges',
            'description' => "Converted from medication total: {$medicationTotal} DA",
            'is_medication_conversion' => true,
            'status' => 'completed',
        ]);
    }
}
