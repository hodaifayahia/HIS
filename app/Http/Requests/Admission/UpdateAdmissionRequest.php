<?php

namespace App\Http\Requests\Admission;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdmissionRequest extends FormRequest
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
            'doctor_id' => 'nullable|exists:doctors,id',
            'companion_id' => 'nullable|exists:patients,id|different:patient_id',
            'status' => 'sometimes|in:admitted,in_service,document_pending,ready_for_discharge',
            'initial_prestation_id' => 'nullable|exists:prestations,id',
            'documents_verified' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Invalid admission status',
            'doctor_id.exists' => 'Selected doctor does not exist',
            'companion_id.exists' => 'Selected companion does not exist',
            'companion_id.different' => 'Companion must be different from the patient',
            'documents_verified.boolean' => 'Documents verified must be true or false',
        ];
    }
}
