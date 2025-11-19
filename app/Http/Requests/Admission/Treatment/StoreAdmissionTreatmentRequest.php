<?php

namespace App\Http\Requests\Admission\Treatment;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdmissionTreatmentRequest extends FormRequest
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
            'prestation_id' => 'nullable|exists:prestations,id',
            'entered_at' => 'required|date',
            'exited_at' => 'nullable|date|after:entered_at',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'entered_at.required' => 'Entry date and time is required',
            'exited_at.after' => 'Exit time must be after entry time',
        ];
    }
}
