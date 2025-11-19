<?php

namespace App\Http\Requests\Nursing;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientConsumptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fiche_id' => ['sometimes', 'exists:fiche_navettes,id'],
            'product_id' => ['sometimes', 'exists:products,id'],
            'product_pharmacy_id' => ['nullable', 'exists:pharmacy_products,id'],
            'consumed_by' => ['nullable', 'integer'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
