<?php

namespace App\Http\Requests\Reception;

use Illuminate\Foundation\Http\FormRequest;

class StoreFicheNavetteRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => [
                'required',
                'integer',
                'exists:patients,id',
            ],
            'companion_id' => [
                'nullable',
                'integer',
                'exists:patients,id',
                'different:patient_id',
            ],
            'is_emergency' => [
                'nullable',
                'boolean',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'fiche_date' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'Patient selection is required.',
            'patient_id.exists' => 'Selected patient does not exist.',
            'companion_id.exists' => 'Selected companion does not exist.',
            'companion_id.different' => 'Companion must be different from the patient.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
            'fiche_date.after_or_equal' => 'Fiche date cannot be in the past.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'patient_id' => 'patient',
            'companion_id' => 'companion',
            'is_emergency' => 'emergency status',
            'fiche_date' => 'fiche date',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string 'true'/'false' to boolean if needed
        if ($this->has('is_emergency') && is_string($this->is_emergency)) {
            $this->merge([
                'is_emergency' => filter_var($this->is_emergency, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }

        // Set default fiche_date if not provided
        if (! $this->has('fiche_date')) {
            $this->merge([
                'fiche_date' => now()->toDateString(),
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation logic can go here

            // Example: Check if patient already has an active fiche today
            if (! $validator->errors()->has('patient_id') && $this->patient_id) {
                $existingFiche = \App\Models\Reception\ficheNavette::where('patient_id', $this->patient_id)
                    ->whereDate('fiche_date', now())
                    ->where('status', '!=', 'completed')
                    ->exists();

                if ($existingFiche) {
                    $validator->errors()->add('patient_id', 'Patient already has an active fiche today.');
                }
            }
        });
    }
}
