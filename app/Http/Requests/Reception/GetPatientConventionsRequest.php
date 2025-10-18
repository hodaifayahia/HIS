<?php

namespace App\Http\Requests\Reception;

use Illuminate\Foundation\Http\FormRequest;

class GetPatientConventionsRequest extends FormRequest
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
            'fiche_navette_id' => 'required|exists:fiche_navettes,id',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'fiche_navette_id.required' => 'The fiche navette ID is required.',
            'fiche_navette_id.exists' => 'The selected fiche navette is invalid.',
        ];
    }
}