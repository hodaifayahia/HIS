<?php

namespace App\Http\Requests\Reception;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFicheNavetteItemRequest extends FormRequest
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
            'doctor_id' => 'nullable|exists:users,id',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled,required,dependency',
            'custom_name' => 'nullable|string',
            'convention_id' => 'nullable|exists:conventions,id',
            'family_authorization' => 'nullable|array',
            'family_authorization.*' => 'string|in:ascendant,descendant,Conjoint,adherent,autre',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'doctor_id.exists' => 'The selected doctor is invalid.',
            'status.in' => 'The status must be one of: pending, in_progress, completed, cancelled, required, dependency.',
            'family_authorization.*.in' => 'Family authorization must be one of: ascendant, descendant, Conjoint, adherent, autre.',
        ];
    }
}