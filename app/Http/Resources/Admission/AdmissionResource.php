<?php

namespace App\Http\Resources\Admission;

use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        // Pre-calculate values to avoid repeated method calls
        $status = $this->status;
        $documentsVerified = (bool) $this->documents_verified;

        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'patient' => $this->whenLoaded('patient', [
                'id' => $this->patient->id ?? null,
                'first_name' => $this->patient->Firstname ?? $this->patient->first_name ?? '',
                'last_name' => $this->patient->Lastname ?? $this->patient->last_name ?? '',
                'name' => ($this->patient->Firstname ?? $this->patient->first_name ?? '').' '.($this->patient->Lastname ?? $this->patient->last_name ?? ''),
                'phone' => $this->patient->phone ?? null,
            ]),
            'doctor_id' => $this->doctor_id,
            'doctor' => $this->whenLoaded('doctor', [
                'id' => $this->doctor->id ?? null,
                'name' => $this->doctor->user->name ?? 'N/A',
            ]),
            'companion_id' => $this->companion_id,
            'companion' => $this->whenLoaded('companion', [
                'id' => $this->companion->id ?? null,
                'first_name' => $this->companion->Firstname ?? $this->companion->first_name ?? '',
                'last_name' => $this->companion->Lastname ?? $this->companion->last_name ?? '',
                'name' => ($this->companion->Firstname ?? $this->companion->first_name ?? '').' '.($this->companion->Lastname ?? $this->companion->last_name ?? ''),
                'phone' => $this->companion->phone ?? null,
            ]),

            // New fields
            'file_number' => $this->file_number,
            'file_number_verified' => (bool) $this->file_number_verified,
            'observation' => $this->observation,
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', [
                'id' => $this->company->id ?? null,
                'name' => $this->company->name ?? null,
            ]),
            'social_security_num' => $this->social_security_num,
            'relation_type' => $this->relation_type,
            'relation_type_label' => $this->relation_type ? config('relation_types.'.$this->relation_type) : null,
            'reason_for_admission' => $this->reason_for_admission,

            'type' => $this->type,
            'type_label' => ucfirst($this->type),
            'status' => $status,
            'status_label' => ucfirst(str_replace('_', ' ', $status)),
            'admitted_at' => $this->admitted_at?->format('Y-m-d H:i:s'),
            'discharged_at' => $this->discharged_at?->format('Y-m-d H:i:s'),
            'duration_days' => $this->when(
                $this->admitted_at,
                fn () => $this->admitted_at->diffInDays($this->discharged_at ?? now()),
                0
            ),

            // Initial prestation
            'initial_prestation_id' => $this->initial_prestation_id,
            'initial_prestation' => $this->whenLoaded('initialPrestation', [
                'id' => $this->initialPrestation->id ?? null,
                'name' => $this->initialPrestation->name ?? null,
                'code' => $this->initialPrestation->internal_code ?? null,
            ]),

            'documents_verified' => $documentsVerified,

            // Fiche Navette reference
            'fiche_navette_id' => $this->fiche_navette_id,

            // Relationships counts (only when explicitly counted)
            'procedures_count' => $this->whenCounted('procedures'),
            'documents_count' => $this->whenCounted('documents'),
            'billing_records_count' => $this->whenCounted('billingRecords'),
            'treatments_count' => $this->whenCounted('treatments'),

            // Procedures (only when loaded in detail view)
            'procedures' => AdmissionProcedureResource::collection($this->whenLoaded('procedures')),

            // Documents (only when loaded in detail view)
            'documents' => AdmissionDocumentResource::collection($this->whenLoaded('documents')),

            // Billing records (only when loaded in detail view)
            'billing_records' => AdmissionBillingRecordResource::collection($this->whenLoaded('billingRecords')),

            // Treatments (only when loaded in detail view)
            'treatments' => AdmissionTreatmentResource::collection($this->whenLoaded('treatments')),

            // System fields
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', [
                'id' => $this->creator->id ?? null,
                'name' => $this->creator->name ?? null,
            ]),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            // Computed flags (inline for performance)
            'can_discharge' => $documentsVerified && $status !== 'ready_for_discharge',
            'is_active' => in_array($status, ['admitted', 'in_service', 'document_pending'], true),
            'can_edit_file_number' => ! $this->file_number_verified,
        ];
    }
}
