<?php

namespace App\Http\Requests\Reception;

use Illuminate\Foundation\Http\FormRequest;

class GetPrestationsWithConventionPricingRequest extends FormRequest
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
            'convention_ids' => 'required|string',
            'prise_en_charge_date' => 'required|date',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'convention_ids.required' => 'The convention IDs are required.',
            'prise_en_charge_date.required' => 'The prise en charge date is required.',
            'prise_en_charge_date.date' => 'The prise en charge date must be a valid date.',
        ];
    }
}