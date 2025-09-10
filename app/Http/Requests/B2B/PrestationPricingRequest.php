<?php

namespace App\Http\Requests\B2B;

use Illuminate\Foundation\Http\FormRequest;

class PrestationPricingRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Adjust based on your authorization logic
    }

    public function rules()
    {
        return [
            'avenant_id' => [
                'nullable',
                'integer',
                'exists:avenants,id' // This validates that the avenant exists
            ],
            'annex_id' => [
                'nullable',
                'integer',
                'exists:annexes,id' // This validates that the avenant exists
            ],
            'prestation_id' => [
                'nullable',
                'integer',
                'exists:prestations,id' // This validates that the prestation exists
            ],
            'subname' => [
                'nullable',
                'string',
                'max:255' // Adjust max length as needed
            ],
            'prix' => [
                'required',
                'numeric',
                'min:0'
            ],
        ];
    }

    public function messages()
    {
        return [
            'avenant_id.exists' => 'The selected avenant does not exist.',
            'prestation_id.exists' => 'The selected prestation does not exist.',
            'prix.required' => 'Price is required.',
            'prix.numeric' => 'Price must be a valid number.',
            'prix.min' => 'Price cannot be negative.',
        ];
    }
}
