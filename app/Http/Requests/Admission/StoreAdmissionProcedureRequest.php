<?php

namespace App\Http\Requests\Admission;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdmissionProcedureRequest extends FormRequest
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
            'prestation_id' => 'nullable|exists:prestations,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'performed_by' => 'nullable|exists:users,id',
            'scheduled_at' => 'nullable|date',
            'is_medication_conversion' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Procedure name is required',
            'prestation_id.exists' => 'Selected prestation does not exist',
            'performed_by.exists' => 'Selected staff member does not exist',
        ];
    }
}
