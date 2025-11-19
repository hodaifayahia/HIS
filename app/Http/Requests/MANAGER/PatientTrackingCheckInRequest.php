<?php

namespace App\Http\Requests\MANAGER;

use Illuminate\Foundation\Http\FormRequest;

class PatientTrackingCheckInRequest extends FormRequest
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
            'fiche_navette_item_id' => 'required|integer|exists:fiche_navette_items,id',
            'salle_id' => 'required|integer|exists:salls,id',
            'notes' => 'nullable|string|max:1000',
            'update_item_status' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'fiche_navette_item_id' => 'fiche navette item',
            'salle_id' => 'salle',
            'notes' => 'notes',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'fiche_navette_item_id.required' => 'The fiche navette item is required.',
            'fiche_navette_item_id.exists' => 'The selected fiche navette item does not exist.',
            'salle_id.required' => 'Please select a salle.',
            'salle_id.exists' => 'The selected salle does not exist.',
        ];
    }
}
