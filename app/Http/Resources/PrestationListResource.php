<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrestationListResource extends JsonResource
{
    /**
     * Transform the resource into an array for list views.
     * This includes all essential fields for the list and detail views.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'internal_code' => $this->internal_code,
            'code' => $this->code,
            'billing_code' => $this->billing_code,
            'type' => $this->type,

            // Relations
            'service' => $this->when($this->relationLoaded('service'), [
                'id' => $this->service?->id,
                'name' => $this->service?->name,
            ]),

            'specialization' => $this->when($this->relationLoaded('specialization'), [
                'id' => $this->specialization?->id,
                'name' => $this->specialization?->name,
            ]),

            'modality_type' => $this->when($this->relationLoaded('modalityType'), [
                'id' => $this->modalityType?->id,
                'name' => $this->modalityType?->name,
            ]),

            // Pricing & Financial
            'public_price' => $this->public_price,
            'convenience_prix' => $this->convenience_prix,
            'vat_rate' => $this->vat_rate,
            'tva_const_prestation' => $this->tva_const_prestation,
            'consumables_cost' => $this->consumables_cost,
            'min_versement_amount' => $this->min_versement_amount,
            'default_payment_type' => $this->default_payment_type,
            'price_with_vat' => $this->price_with_vat,
            'price_with_vat_and_consumables_variant' => $this->price_with_vat_and_consumables_variant,
            'calculated_public_price' => $this->calculated_public_price,

            // Duration & Timing
            'default_duration_minutes' => $this->default_duration_minutes,
            'formatted_duration' => $this->formatted_duration,
            'night_tariff' => $this->night_tariff,

            // Status flags
            'is_active' => $this->is_active,
            'is_social_security_reimbursable' => $this->is_social_security_reimbursable,
            'requires_hospitalization' => $this->requires_hospitalization,
            'default_hosp_nights' => $this->default_hosp_nights,
            'required_modality_type_id' => $this->required_modality_type_id,
            'need_an_appointment' => $this->need_an_appointment,
            'Urgent_Prestation' => $this->Urgent_Prestation,
            'requires_prescription' => $this->requires_prescription,
            'is_emergency_compatible' => $this->is_emergency_compatible,
            'requires_appointment' => $this->requires_appointment,

            // Night Tariff
            'Tarif_de_nuit' => $this->Tarif_de_nuit,
            'Tarif_de_nuit_is_active' => $this->Tarif_de_nuit_is_active,

            // Fee Distribution
            'fee_distribution_model' => $this->fee_distribution_model,
            'primary_doctor_share' => $this->primary_doctor_share,
            'primary_doctor_is_percentage' => $this->primary_doctor_is_percentage,
            'assistant_doctor_share' => $this->assistant_doctor_share,
            'assistant_doctor_is_percentage' => $this->assistant_doctor_is_percentage,
            'technician_share' => $this->technician_share,
            'technician_is_percentage' => $this->technician_is_percentage,
            'clinic_share' => $this->clinic_share,
            'clinic_is_percentage' => $this->clinic_is_percentage,

            // Conditions & Instructions
            'reimbursement_conditions' => $this->reimbursement_conditions,
            'non_applicable_discount_rules' => $this->non_applicable_discount_rules,
            'required_prestations_info' => $this->required_prestations_info,
            'patient_instructions' => $this->patient_instructions,
            'required_consents' => $this->required_consents,

            // Other
            'formatted_id' => $this->formatted_id,

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
