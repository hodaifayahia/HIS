<?php

namespace App\Services;

use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\Service;
use App\Models\Specialization;
use App\Models\CONFIGURATION\ModalityType;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class PrestationValidationService
{
    /**
     * Required attributes for Prestation model
     */
    private const REQUIRED_ATTRIBUTES = [
        'name',
        'internal_code',
        'billing_code',
        'service_id',
        'specialization_id',
        'type',
        'public_price',
    ];

    /**
     * Optional attributes with default values
     */
    private const OPTIONAL_ATTRIBUTES = [
        'description' => null,
        'convenience_prix' => 0.00,
        'tva_const_prestation' => 0.00,
        'vat_rate' => 20.00,
        'night_tariff' => 0.00,
        'consumables_cost' => 0.00,
        'is_social_security_reimbursable' => false,
        'reimbursement_conditions' => null,
        'non_applicable_discount_rules' => null,
        'fee_distribution_model' => 'percentage',
        'primary_doctor_share' => 60.00,
        'primary_doctor_is_percentage' => true,
        'assistant_doctor_share' => 0.00,
        'assistant_doctor_is_percentage' => true,
        'technician_share' => 0.00,
        'technician_is_percentage' => true,
        'clinic_share' => 40.00,
        'clinic_is_percentage' => true,
        'default_payment_type' => 'post-pay',
        'min_versement_amount' => 0.00,
        'need_an_appointment' => true,
        'requires_hospitalization' => false,
        'default_hosp_nights' => 0,
        'required_modality_type_id' => null,
        'default_duration_minutes' => 30,
        'required_prestations_info' => null,
        'patient_instructions' => null,
        'required_consents' => null,
        'is_active' => true,
        'requires_appointment' => true,
        'requires_prescription' => false,
        'is_emergency_compatible' => false,
    ];

    /**
     * Validate the Prestation model structure
     */
    public function validateModelStructure(): array
    {
        $results = [
            'valid' => true,
            'missing_columns' => [],
            'table_exists' => false,
            'fillable_attributes' => [],
            'casts' => [],
            'errors' => []
        ];

        try {
            // Check if table exists
            $results['table_exists'] = Schema::hasTable('prestations');
            
            if (!$results['table_exists']) {
                $results['valid'] = false;
                $results['errors'][] = 'Prestations table does not exist';
                return $results;
            }

            // Get table columns
            $columns = Schema::getColumnListing('prestations');
            
            // Check for missing required columns
            foreach (self::REQUIRED_ATTRIBUTES as $attribute) {
                if (!in_array($attribute, $columns)) {
                    $results['missing_columns'][] = $attribute;
                    $results['valid'] = false;
                }
            }

            // Get model information
            $model = new Prestation();
            $results['fillable_attributes'] = $model->getFillable();
            $results['casts'] = $model->getCasts();

            // Validate relationships exist
            $relationshipValidation = $this->validateRelationships();
            if (!$relationshipValidation['valid']) {
                $results['valid'] = false;
                $results['errors'] = array_merge($results['errors'], $relationshipValidation['errors']);
            }

        } catch (\Exception $e) {
            $results['valid'] = false;
            $results['errors'][] = 'Model structure validation failed: ' . $e->getMessage();
            Log::error('Prestation model validation error', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * Validate required relationships exist
     */
    private function validateRelationships(): array
    {
        $results = ['valid' => true, 'errors' => []];

        try {
            // Check if Service model and table exist
            if (!Schema::hasTable('services')) {
                $results['valid'] = false;
                $results['errors'][] = 'Services table does not exist';
            }

            // Check if Specialization model and table exist
            if (!Schema::hasTable('specializations')) {
                $results['valid'] = false;
                $results['errors'][] = 'Specializations table does not exist';
            }

            // Check if ModalityType model and table exist (optional relationship)
            if (!Schema::hasTable('modality_types')) {
                Log::warning('ModalityTypes table does not exist - optional relationship');
            }

        } catch (\Exception $e) {
            $results['valid'] = false;
            $results['errors'][] = 'Relationship validation failed: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Get required attributes for prestation
     */
    public function getRequiredAttributes(): array
    {
        return self::REQUIRED_ATTRIBUTES;
    }

    /**
     * Add missing optional attributes with default values
     */
    public function addMissingOptionalAttributes(array $data): array
    {
        $result = [
            'data' => $data,
            'added_attributes' => []
        ];

        foreach (self::OPTIONAL_ATTRIBUTES as $attribute => $defaultValue) {
            if (!array_key_exists($attribute, $data)) {
                $result['data'][$attribute] = $defaultValue;
                $result['added_attributes'][] = $attribute;
            }
        }

        return $result;
    }

    /**
     * Validate data types
     */
    public function validateDataTypes(array $data): array
    {
        $results = ['valid' => true, 'errors' => []];

        $typeRules = [
            'public_price' => 'numeric',
            'vat_rate' => 'numeric',
            'min_versement_amount' => 'numeric',
            'primary_doctor_share' => 'numeric',
            'assistant_doctor_share' => 'numeric',
            'technician_share' => 'numeric',
            'clinic_share' => 'numeric',
            'convenience_prix' => 'numeric',
            'tva_const_prestation' => 'numeric',
            'night_tariff' => 'numeric',
            'consumables_cost' => 'numeric',
            'default_duration_minutes' => 'integer',
            'service_id' => 'integer',
            'specialization_id' => 'integer',
            'default_hosp_nights' => 'integer',
            'required_modality_type_id' => 'integer',
            'is_active' => 'boolean',
            'is_social_security_reimbursable' => 'boolean',
            'primary_doctor_is_percentage' => 'boolean',
            'assistant_doctor_is_percentage' => 'boolean',
            'technician_is_percentage' => 'boolean',
            'clinic_is_percentage' => 'boolean',
            'requires_appointment' => 'boolean',
            'requires_prescription' => 'boolean',
            'is_emergency_compatible' => 'boolean',
            'need_an_appointment' => 'boolean',
            'requires_hospitalization' => 'boolean',
        ];

        foreach ($typeRules as $field => $expectedType) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];
                $isValid = false;

                switch ($expectedType) {
                    case 'numeric':
                        $isValid = is_numeric($value);
                        break;
                    case 'integer':
                        $isValid = is_int($value) || (is_string($value) && ctype_digit($value)) || is_null($value);
                        break;
                    case 'boolean':
                        $isValid = is_bool($value);
                        break;
                }

                if (!$isValid) {
                    $results['valid'] = false;
                    $results['errors'][] = "Field '{$field}' must be {$expectedType}";
                }
            }
        }

        return $results;
    }

    /**
     * Validate business rules
     */
    public function validateBusinessRules(array $data): array
    {
        $results = ['valid' => true, 'errors' => [], 'warnings' => []];

        // Validate price constraints
        if (isset($data['public_price']) && $data['public_price'] < 0) {
            $results['valid'] = false;
            $results['errors'][] = 'Public price cannot be negative';
        }

        // Validate VAT rate
        if (isset($data['vat_rate']) && ($data['vat_rate'] < 0 || $data['vat_rate'] > 100)) {
            $results['valid'] = false;
            $results['errors'][] = 'VAT rate must be between 0 and 100';
        }

        // Validate duration
        if (isset($data['default_duration_minutes']) && $data['default_duration_minutes'] < 0) {
            $results['valid'] = false;
            $results['errors'][] = 'Duration cannot be negative';
        }

        // Validate share percentages
        $shareFields = ['primary_doctor_share', 'assistant_doctor_share', 'technician_share', 'clinic_share'];
        foreach ($shareFields as $field) {
            if (isset($data[$field]) && ($data[$field] < 0 || $data[$field] > 100)) {
                $results['valid'] = false;
                $results['errors'][] = 'Share percentages must be between 0 and 100';
                break;
            }
        }

        // Validate total percentage shares
        $totalPercentage = 0;
        $hasPercentageShares = false;
        
        foreach ($shareFields as $field) {
            $isPercentageField = str_replace('_share', '_is_percentage', $field);
            if (isset($data[$field]) && isset($data[$isPercentageField]) && $data[$isPercentageField]) {
                $totalPercentage += $data[$field];
                $hasPercentageShares = true;
            }
        }

        if ($hasPercentageShares && $totalPercentage != 100) {
            $results['warnings'][] = "Total percentage shares ({$totalPercentage}%) do not equal 100%";
        }

        // Validate relationships exist
        if (isset($data['service_id'])) {
            if (!Service::find($data['service_id'])) {
                $results['valid'] = false;
                $results['errors'][] = "Service with ID {$data['service_id']} does not exist";
            }
        }

        if (isset($data['specialization_id'])) {
            if (!Specialization::find($data['specialization_id'])) {
                $results['valid'] = false;
                $results['errors'][] = "Specialization with ID {$data['specialization_id']} does not exist";
            }
        }

        return $results;
    }

    /**
     * Validate uniqueness constraints
     */
    public function validateUniqueness(array $data): array
    {
        $results = ['valid' => true, 'errors' => []];

        try {
            // Check internal_code uniqueness
            if (isset($data['internal_code'])) {
                $existing = Prestation::where('internal_code', $data['internal_code'])->first();
                if ($existing) {
                    $results['valid'] = false;
                    $results['errors'][] = "Internal code '{$data['internal_code']}' already exists";
                }
            }

            // Check billing_code uniqueness
            if (isset($data['billing_code'])) {
                $existing = Prestation::where('billing_code', $data['billing_code'])->first();
                if ($existing) {
                    $results['valid'] = false;
                    $results['errors'][] = "Billing code '{$data['billing_code']}' already exists";
                }
            }

        } catch (\Exception $e) {
            $results['valid'] = false;
            $results['errors'][] = 'Uniqueness validation failed: ' . $e->getMessage();
            Log::error('Prestation uniqueness validation error', ['error' => $e->getMessage(), 'data' => $data]);
        }

        return $results;
    }

    /**
     * Validate and prepare prestation data
     */
    public function validateAndPrepareData(array $data): array
    {
        $results = [
            'valid' => true,
            'data' => $data,
            'errors' => [],
            'warnings' => [],
            'added_attributes' => []
        ];

        try {
            // Step 1: Validate model structure
            $structureValidation = $this->validateModelStructure();
            if (!$structureValidation['valid']) {
                $results['valid'] = false;
                $results['errors'] = array_merge($results['errors'], $structureValidation['errors']);
                return $results;
            }

            // Step 2: Check required attributes
            foreach (self::REQUIRED_ATTRIBUTES as $attribute) {
                if (!isset($data[$attribute]) || $data[$attribute] === '' || $data[$attribute] === null) {
                    $results['valid'] = false;
                    $results['errors'][] = "Required attribute '{$attribute}' is missing or empty";
                }
            }

            if (!$results['valid']) {
                return $results;
            }

            // Step 3: Add missing optional attributes with defaults
            $attributeResult = $this->addMissingOptionalAttributes($results['data']);
            $results['data'] = $attributeResult['data'];
            $results['added_attributes'] = $attributeResult['added_attributes'];

            // Step 4: Validate data types
            $typeValidation = $this->validateDataTypes($results['data']);
            if (!$typeValidation['valid']) {
                $results['valid'] = false;
                $results['errors'] = array_merge($results['errors'], $typeValidation['errors']);
            }

            // Step 5: Validate business rules
            $businessValidation = $this->validateBusinessRules($results['data']);
            if (!$businessValidation['valid']) {
                $results['valid'] = false;
                $results['errors'] = array_merge($results['errors'], $businessValidation['errors']);
            }
            if (!empty($businessValidation['warnings'])) {
                $results['warnings'] = array_merge($results['warnings'], $businessValidation['warnings']);
            }

            // Step 6: Validate uniqueness
            $uniquenessValidation = $this->validateUniqueness($results['data']);
            if (!$uniquenessValidation['valid']) {
                $results['valid'] = false;
                $results['errors'] = array_merge($results['errors'], $uniquenessValidation['errors']);
            }

        } catch (\Exception $e) {
            $results['valid'] = false;
            $results['errors'][] = 'Data validation failed: ' . $e->getMessage();
            Log::error('Prestation data validation error', ['error' => $e->getMessage(), 'data' => $data]);
        }

        return $results;
    }
}