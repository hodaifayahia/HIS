<?php

namespace App\Http\Requests\Admission;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdmissionRequest extends FormRequest
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
        return [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'companion_id' => 'nullable|exists:patients,id|different:patient_id',
            'type' => 'required|in:surgery,nursing',
            'initial_prestation_id' => 'nullable|exists:prestations,id',
            'fiche_navette_id' => 'nullable|exists:fiche_navettes,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'patient_id.required' => 'Patient is required',
            'patient_id.exists' => 'Selected patient does not exist',
            'type.required' => 'Admission type is required',
            'type.in' => 'Admission type must be either surgery or nursing',
            'initial_prestation_id.exists' => 'Selected prestation does not exist',
            'doctor_id.exists' => 'Selected doctor does not exist',
            'companion_id.exists' => 'Selected companion does not exist',
            'companion_id.different' => 'Companion must be different from the patient',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // For surgery type, initial prestation is required
            if ($this->type === 'surgery' && empty($this->initial_prestation_id)) {
                $validator->errors()->add(
                    'initial_prestation_id',
                    'Initial prestation is required for surgery admission'
                );
            }
        });
    }
}
