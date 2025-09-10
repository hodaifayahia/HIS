<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrestationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'internal_code' => $this->internal_code,
            'need_an_appointment' => $this->need_an_appointment,
            'billing_code' => $this->billing_code,
            'description' => $this->description,
            'convenience_prixe' => $this->convenience_price,
            'service_id' => $this->service_id,
            'specialization_id' => $this->specialization_id,
            'service' => [
                'id' => $this->service->id,
                'name' => $this->service->name,
            ],
            // Ensure specialization relation is loaded, or handle null if it can be
            'specialization' => $this->whenLoaded('specialization', function () {
                return [
                    'id' => $this->specialization->id,
                    'name' => $this->specialization->name,
                    'service_id' => $this->specialization->service_id,

                ];
            }, null), // Return null if not loaded or not set
            'type' => $this->type, // <--- ADDED
            'modality_type' => $this->whenLoaded('modalityType', function () {
                return [
                    'id' => $this->modalityType->id,
                    'name' => $this->modalityType->name,
                ];
            }, null), // Use whenLoaded for relations
            'public_price' => $this->public_price,
            'vat_rate' => $this->vat_rate,
            'price_with_vat' => $this->price_with_vat, // This is likely an accessor on your model
            'night_tariff' => $this->night_tariff, // <--- ADDED (assuming consistent naming now)
            'consumables_cost' => $this->consumables_cost,
            'is_social_security_reimbursable' => $this->is_social_security_reimbursable,
            'reimbursement_conditions' => $this->reimbursement_conditions, // <--- ADDED
            'non_applicable_discount_rules' => $this->non_applicable_discount_rules, // <--- ADDED (will be array due to $casts)
            'fee_distribution_model' => $this->fee_distribution_model, // <--- ADDED

            // Fee Distribution Shares
            'primary_doctor_share' => $this->primary_doctor_share, // <--- ADDED
            'primary_doctor_is_percentage' => $this->primary_doctor_is_percentage, // <--- ADDED
            'assistant_doctor_share' => $this->assistant_doctor_share, // <--- ADDED
            'assistant_doctor_is_percentage' => $this->assistant_doctor_is_percentage, // <--- ADDED
            'technician_share' => $this->technician_share, // <--- ADDED
            'technician_is_percentage' => $this->technician_is_percentage, // <--- ADDED
            'clinic_share' => $this->clinic_share, // <--- ADDED
            'clinic_is_percentage' => $this->clinic_is_percentage, // <--- ADDED

            'default_payment_type' => $this->default_payment_type,
            'min_versement_amount' => $this->min_versement_amount,

            // Operational & Clinical Configuration
            'requires_hospitalization' => $this->requires_hospitalization,
            'default_hosp_nights' => $this->default_hosp_nights,
            'required_modality_type_id' => $this->required_modality_type_id, // You might want to include the modality_type object directly if it's always loaded.
            'default_duration_minutes' => $this->default_duration_minutes,
            'formatted_duration' => $this->formatted_duration, // This is likely an accessor on your model
            'required_prestations_info' => $this->required_prestations_info, // <--- ADDED (will be array due to $casts)
            'patient_instructions' => $this->patient_instructions, // <--- ADDED
            'required_consents' => $this->required_consents, // <--- ADDED (will be array due to $casts)

            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}