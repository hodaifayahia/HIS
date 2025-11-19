<?php

namespace App\Http\Requests\Nursing;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientConsumptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Validate array of consumptions
        return [
            'consumptions' => ['required', 'array', 'min:1'],
            'consumptions.*.fiche_id' => ['required', 'exists:fiche_navettes,id'],
            'consumptions.*.product_id' => ['required', 'exists:products,id'],
            'consumptions.*.product_pharmacy_id' => ['nullable', 'exists:pharmacy_products,id'],
            'consumptions.*.consumed_by' => ['nullable', 'integer'],
            'consumptions.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // If user submits a single object {fiche_id:..}, wrap into array
        if ($this->has('fiche_id')) {
            $this->merge([
                'consumptions' => [$this->all()],
            ]);
        }
    }
}
