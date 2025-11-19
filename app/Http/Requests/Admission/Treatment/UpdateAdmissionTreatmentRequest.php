<?php

namespace App\Http\Requests\Admission\Treatment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdmissionTreatmentRequest extends FormRequest
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
            'doctor_id' => 'sometimes|nullable|exists:doctors,id',
            'prestation_id' => 'sometimes|nullable|exists:prestations,id',
            'entered_at' => 'sometimes|date',
            'exited_at' => 'sometimes|nullable|date|after:entered_at',
            'notes' => 'sometimes|nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'exited_at.after' => 'Exit time must be after entry time',
        ];
    }
}
