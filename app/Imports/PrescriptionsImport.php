<?php

namespace App\Imports;

use App\Models\CONFIGURATION\Prestation;
use App\Models\CONFIGURATION\Service;       // Correct path for Service model
use App\Models\Specialization;              // Correct path for Specialization model
use App\Models\CONFIGURATION\ModalityType;  // Correct path for ModalityType model

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors; // Ensure this is present
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Validators\Failure;

use Illuminate\Support\Facades\Log; // For logging general errors
use Throwable;

class PrescriptionsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure, WithBatchInserts, WithChunkReading
{
    use SkipsErrors;

    protected $failures = [];

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Null coalescing operator (??) is good for providing fallbacks
        // but remember validation happens *before* this method is called.
        // So, if 'name' is required, it must be present in the Excel for validation to pass.

        // Find or create related models by name.
        // Ensure you have these models correctly namespaced and defined.
        $service = Service::firstOrCreate(['name' => $row['service_name']]);
        $specialization = Specialization::firstOrCreate(['name' => $row['specialization_name']]);
        $modalityType = ModalityType::firstOrCreate(['name' => $row['required_modality_type_name']]);

        return new Prestation([
            'name'                              => $row['name'],
            'internal_code'                     => $row['internal_code'] ?? null,
            'billing_code'                      => $row['billing_code'] ?? null,
            'description'                       => $row['description'] ?? null,
            'service_id'                        => $service->id,
            'specialization_id'                 => $specialization->id,
            'type'                              => $row['type'] ?? 'Consultation',
            'public_price'                      => $row['public_price'], // Required by validation
            'vat_rate'                          => $row['vat_rate'] ?? 0.00,
            'night_tariff'                      => $row['night_tariff'] ?? 0.00,
            'consumables_cost'                  => $row['consumables_cost'] ?? 0.00,
            'is_social_security_reimbursable'   => (bool)($row['is_social_security_reimbursable'] ?? 0), // Cast directly to boolean
            'reimbursement_conditions'          => $row['reimbursement_conditions'] ?? null,
            'non_applicable_discount_rules'     => $row['non_applicable_discount_rules'] ?? null,
            'fee_distribution_model'            => $row['fee_distribution_model'] ?? null,
            'primary_doctor_share'              => $row['primary_doctor_share'] ?? 0.00,
            'primary_doctor_is_percentage'      => (bool)($row['primary_doctor_is_percentage'] ?? 0), // Cast directly to boolean
            'assistant_doctor_share'            => $row['assistant_doctor_share'] ?? 0.00,
            'assistant_doctor_is_percentage'    => (bool)($row['assistant_doctor_is_percentage'] ?? 0), // Cast directly to boolean
            'technician_share'                  => $row['technician_share'] ?? 0.00,
            'technician_is_percentage'          => (bool)($row['technician_is_percentage'] ?? 0), // Cast directly to boolean
            'clinic_share'                      => $row['clinic_share'] ?? 0.00,
            'clinic_is_percentage'              => (bool)($row['clinic_is_percentage'] ?? 0), // Cast directly to boolean
            'default_payment_type'              => $row['default_payment_type'] ?? 'Cash',
            'min_versement_amount'              => $row['min_versement_amount'] ?? 0.00,
            'requires_hospitalization'          => (bool)($row['requires_hospitalization'] ?? 0), // Cast directly to boolean
            'default_hosp_nights'               => $row['default_hosp_nights'] ?? 0,
            'required_modality_type_id'         => $modalityType->id,
            'default_duration_minutes'          => $row['default_duration_minutes'] ?? 0,
            'required_prestations_info'         => $row['required_prestations_info'] ?? null,
            'patient_instructions'              => $row['patient_instructions'] ?? null,
            'required_consents'                 => $row['required_consents'] ?? null,
            'is_active'                         => (bool)($row['is_active'] ?? 0), // Cast directly to boolean
        ]);
    }

    public function rules(): array
    {
        return [
            // 'name' is required and must exist
            // 'name' => 'required|string|max:255',
            // 'internal_code' => 'nullable|string|max:255|unique:prestations,internal_code', // Assuming 'prestations' is your table name
            // 'billing_code' => 'nullable|string|max:255',
            // 'description' => 'nullable|string',

            // // For exists rules, the value *must* exist in the database.
            // // If the service/specialization/modality type needs to be created on the fly,
            // // the 'exists' rule should be removed here.
            // // However, your model() method uses firstOrCreate which is fine,
            // // but if 'service_name' is missing or misspelled in Excel, 'exists' will fail.
            // // The firstOrCreate will only run *if* validation passes.
            // 'service_name' => 'required|string|max:255', // Changed to required to prevent empty values causing firstOrCreate issues
            // 'specialization_name' => 'required|string|max:255', // Changed to required
            // 'required_modality_type_name' => 'required|string|max:255', // Changed to required

            // 'type' => 'nullable|string|in:Consultation,Procedure,Laboratory Test,Imaging,Vaccination,Other',
            // 'public_price' => 'required|numeric|min:0',
            // 'vat_rate' => 'nullable|numeric|min:0|max:100',
            // 'night_tariff' => 'nullable|numeric|min:0',
            // 'consumables_cost' => 'nullable|numeric|min:0',

            // // These must strictly be 0 or 1 in the Excel file for Laravel's boolean validator
            // 'is_social_security_reimbursable' => 'required|boolean',
            // 'reimbursement_conditions' => 'nullable|string',
            // 'non_applicable_discount_rules' => 'nullable|string',
            // 'fee_distribution_model' => 'nullable|string',
            // 'primary_doctor_share' => 'nullable|numeric|min:0',
            // 'primary_doctor_is_percentage' => 'nullable|boolean', // Expect 0 or 1
            // 'assistant_doctor_share' => 'nullable|numeric|min:0',
            // 'assistant_doctor_is_percentage' => 'nullable|boolean', // Expect 0 or 1
            // 'technician_share' => 'nullable|numeric|min:0',
            // 'technician_is_percentage' => 'nullable|boolean', // Expect 0 or 1
            // 'clinic_share' => 'nullable|numeric|min:0',
            // 'clinic_is_percentage' => 'nullable|boolean', // Expect 0 or 1

            // 'default_payment_type' => 'nullable|string|in:Cash,Card,Insurance,Other', // Ensure values match your system
            // 'min_versement_amount' => 'nullable|numeric|min:0',
            // 'requires_hospitalization' => 'required|boolean', // Expect 0 or 1
            // 'default_hosp_nights' => 'nullable|integer|min:0',

            // 'default_duration_minutes' => 'nullable|integer|min:0',
            // 'required_prestations_info' => 'nullable|string',
            // 'patient_instructions' => 'nullable|string',
            // 'required_consents' => 'nullable|string',
            // 'is_active' => 'required|boolean', // Expect 0 or 1
        ];
    }

    public function customValidationMessages()
    {
        return [
            // 'name.required' => 'The prescription name is required.',
            // 'internal_code.unique' => 'A prestation with this internal code already exists.',
            // 'billing_code.string' => 'The billing code must be a string.', // Specific message for your new error
            // 'public_price.required' => 'The public price is required.',
            // 'public_price.numeric' => 'The public price must be a number.',
            // 'service_name.required' => 'The service name is required.',
            // 'service_name.exists' => 'The specified service does not exist in the database. Please add it first or correct the name.',
            // 'specialization_name.required' => 'The specialization name is required.',
            // 'specialization_name.exists' => 'The specified specialization does not exist in the database. Please add it first or correct the name.',
            // 'required_modality_type_name.required' => 'The required modality type name is required.',
            // 'required_modality_type_name.exists' => 'The specified modality type does not exist in the database. Please add it first or correct the name.',
            // 'is_social_security_reimbursable.required' => 'Social security reimbursable status is required.',
            // 'is_social_security_reimbursable.boolean' => 'Social security reimbursable must be 1 (true) or 0 (false).',
            // 'primary_doctor_is_percentage.boolean' => 'Primary doctor percentage must be 1 (true) or 0 (false).',
            // 'assistant_doctor_is_percentage.boolean' => 'Assistant doctor percentage must be 1 (true) or 0 (false).',
            // 'technician_is_percentage.boolean' => 'Technician percentage must be 1 (true) or 0 (false).',
            // 'clinic_is_percentage.boolean' => 'Clinic percentage must be 1 (true) or 0 (false).',
            // 'requires_hospitalization.required' => 'Hospitalization requirement is required.',
            // 'requires_hospitalization.boolean' => 'Hospitalization requirement must be 1 (true) or 0 (false).',
            // 'is_active.required' => 'Active status is required.',
            // 'is_active.boolean' => 'Active status must be 1 (true) or 0 (false).',
        ];
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function onError(Throwable $e)
    {
        Log::error("Prescription Import Error (General): " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    }

    /**
     * @param Failure ...$failures
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failures[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
            Log::warning("Prescription Import Failure: ", [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
            ]);
        }
    }

    public function getFailures()
    {
        return $this->failures;
    }
}