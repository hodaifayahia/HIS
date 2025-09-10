<?php

namespace App\Http\Requests\CONFIGURATION;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrestationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $prestationId = $this->route('prestation') ?? $this->route('id');

        return [
            'name' => 'required|string|max:255',
            'internal_code' => [
                'string',
                'max:50',
            ],
            'need_an_appointment' => 'boolean',
            'billing_code' => 'nullable|string|max:50',
            'description' => 'nullable|string',

            'service_id' => 'required|exists:services,id',
            'specialization_id' => 'nullable|exists:specializations,id',
            'type' => 'required|in:Médical,Chirurgical',
            'is_active' => 'boolean',

            // Financial Configuration
            'public_price' => 'required|numeric|min:0',
            'convenience_prix' => 'nullable|numeric|min:0',
            
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'night_tariff' => 'nullable|numeric|min:0',
            'consumables_cost' => 'nullable|numeric|min:0',
            'is_social_security_reimbursable' => 'boolean',
            'reimbursement_conditions' => 'nullable|string',
            'non_applicable_discount_rules' => 'nullable|array',
            'fee_distribution_model' => 'nullable|in:percentage,fixed',
            'primary_doctor_share' => 'nullable|string',
            'primary_doctor_is_percentage' => 'boolean',
            'assistant_doctor_share' => 'nullable|string',
            'assistant_doctor_is_percentage' => 'boolean',
            'technician_share' => 'nullable|string',
            'technician_is_percentage' => 'boolean',
            'clinic_share' => 'nullable|string',
            'clinic_is_percentage' => 'boolean',
            'default_payment_type' => 'nullable|in:Pré-paiement,Post-paiement,Versement',
            'min_versement_amount' => 'nullable|numeric|min:0',

            // Operational & Clinical Configuration
            'requires_hospitalization' => 'boolean',
            'default_hosp_nights' => 'nullable|integer|min:1',
            'required_modality_type_id' => 'nullable|exists:modality_types,id',
            'default_duration_minutes' => 'nullable|integer|min:1',
            'required_prestations_info' => 'nullable|array',
            'patient_instructions' => 'nullable|string',
            'required_consents' => 'nullable|array',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Prestation name is required.',
            'internal_code.required' => 'Internal code is required.',
            'internal_code.unique' => 'This internal code is already taken.',
            'service_id.required' => 'Service selection is required.',
            'service_id.exists' => 'Selected service does not exist.',
            'type.required' => 'Prestation type is required.',
            'type.in' => 'Prestation type must be either Médical or Chirurgical.',
            'public_price.required' => 'Public price is required.',
            'public_price.min' => 'Public price must be greater than 0.',
            'vat_rate.min' => 'VAT rate cannot be negative.',
            'vat_rate.max' => 'VAT rate cannot exceed 100%.',
            'night_tariff.min' => 'Night tariff must be greater than 0.',
            'consumables_cost.min' => 'Consumables cost cannot be negative.',
            'fee_distribution_model.in' => 'Fee distribution model must be either percentage or fixed.',
            'default_payment_type.in' => 'Payment type must be one of: Pré-paiement, Post-paiement, or Versement.',
            'min_versement_amount.min' => 'Minimum versement amount cannot be negative.',
            'default_hosp_nights.min' => 'Default hospitalization nights must be at least 1.',
            'default_duration_minutes.min' => 'Default duration must be at least 1 minute.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate hospitalization requirements
            if ($this->requires_hospitalization && !$this->default_hosp_nights) {
                $validator->errors()->add(
                    'default_hosp_nights', 
                    'Default hospitalization nights is required when hospitalization is required.'
                );
            }

            // Validate fee distribution percentages
            if ($this->fee_distribution_model === 'percentage') {
                $this->validateFeeDistribution($validator);
            }
        });
    }

    /**
     * Validate fee distribution percentages.
     */
    private function validateFeeDistribution($validator)
    {
        $totalPercentage = 0;
        $shares = [
            'primary_doctor_share' => $this->primary_doctor_is_percentage,
            'assistant_doctor_share' => $this->assistant_doctor_is_percentage,
            'technician_share' => $this->technician_is_percentage,
            'clinic_share' => $this->clinic_is_percentage,
        ];

        foreach ($shares as $shareField => $isPercentage) {
            if ($isPercentage && $this->$shareField !== null && $this->$shareField !== '') { // Added null/empty string check
                $value = str_replace('%', '', $this->$shareField);
                $totalPercentage += floatval($value);
            }
        }

        // Check if all shares are percentages (assuming this logic is correct based on the form's intent)
        // If it's possible for some shares to be fixed while others are percentage, this logic needs adjustment.
        // For now, assuming all four must be percentages if fee_distribution_model is 'percentage'.
        $allArePercentages = $this->primary_doctor_is_percentage &&
                             $this->assistant_doctor_is_percentage &&
                             $this->technician_is_percentage &&
                             $this->clinic_is_percentage;

        // Use a small epsilon for floating-point comparison
        $epsilon = 0.0001; // Or PHP_FLOAT_EPSILON for smaller precision

        // If all are percentages and the total is not approximately 100
        if ($allArePercentages && abs($totalPercentage - 100) > $epsilon) {
            $validator->errors()->add(
                'fee_distribution_shares', 
                "Fee distribution percentages must sum to 100%. Current total: {$totalPercentage}%"
            );
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Convert boolean strings to actual booleans if needed
        $booleanFields = [
            'is_active',
            'is_social_security_reimbursable',
            'primary_doctor_is_percentage',
            'assistant_doctor_is_percentage',
            'technician_is_percentage',
            'clinic_is_percentage',
            'requires_hospitalization'
        ];

        $data = [];
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $data[$field] = filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN);
            }
        }

        if (!empty($data)) {
            $this->merge($data);
        }

        // Also ensure numeric string fields for shares are treated as numbers
        // before floatval, especially if they might come as empty strings from frontend.
        $shareFields = ['primary_doctor_share', 'assistant_doctor_share', 'technician_share', 'clinic_share'];
        foreach ($shareFields as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $this->merge([$field => null]); // Convert empty string to null for numeric handling
            }
        }
    }
}