<?php

namespace App\Http\Requests\Reception;

use Illuminate\Foundation\Http\FormRequest;

class ReceptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Change to true to allow the request to be authorized.
        // You can add more complex authorization logic here if needed.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Use 'sometimes' for all fields in the update action to allow partial updates.
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            return [
                'patient_id' => 'sometimes|required|exists:patients,id',
                'fiche_date' => 'sometimes|required|date',
                'status' => 'sometimes|required|string|max:255',
                'items' => 'nullable|array',
                'items.*.content' => 'required_with:items|string',
                'items.*.notes' => 'nullable|string',
            ];
        }

        // Validation rules for the create action.
        return [
            'patient_id' => 'required|exists:patients,id',
            'fiche_date' => 'required|date',
            'status' => 'required|string|max:255',
            'items' => 'nullable|array',
            'items.*.content' => 'required_with:items|string',
            'items.*.notes' => 'nullable|string',
        ];
    }
}
