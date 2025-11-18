<?php

namespace App\Http\Requests\ServiceDemand;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceDemandRequest extends FormRequest
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
            'service_id' => 'required|exists:services,id',
            'expected_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
            'is_pharmacy_order' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'service_id.required' => 'Service is required',
            'service_id.exists' => 'Selected service does not exist',
            'expected_date.after_or_equal' => 'Expected date must be today or in the future',
            'notes.max' => 'Notes cannot exceed 1000 characters',
        ];
    }
}
