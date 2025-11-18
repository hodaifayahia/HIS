<?php

namespace App\Http\Requests\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class AddExternalPrescriptionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.pharmacy_product_id' => 'required|exists:pharmacy_products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.quantity_by_box' => 'nullable|boolean',
            'items.*.unit' => 'nullable|string|max:50',
        ];
    }
}
