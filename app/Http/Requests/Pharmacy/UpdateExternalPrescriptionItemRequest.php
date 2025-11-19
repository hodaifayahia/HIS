<?php

namespace App\Http\Requests\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExternalPrescriptionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => 'nullable|numeric|min:0.01',
            'quantity_by_box' => 'nullable|boolean',
            'unit' => 'nullable|string|max:50',
        ];
    }
}
