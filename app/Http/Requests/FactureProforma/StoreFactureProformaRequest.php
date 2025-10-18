<?php

namespace App\Http\Requests\FactureProforma;

use Illuminate\Foundation\Http\FormRequest;

class StoreFactureProformaRequest extends FormRequest
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
            'date' => 'nullable|date',
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'service_demand_purchasing_id' => 'nullable|exists:service_demand_purchasings,id',
            'notes' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:draft,sent,confirmed,completed',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.quantity_sended' => 'nullable|integer|min:0',
            'products.*.price' => 'nullable|numeric|min:0',
            'products.*.unit' => 'nullable|string',
            'products.*.confirmation_status' => 'nullable|string|in:pending,confirmed',
            'attachments' => 'nullable|array',
            'attachments.*.file' => 'nullable|file|max:10240', // 10MB max
            'attachments.*.name' => 'nullable|string',
            'attachments.*.type' => 'nullable|string',
            'attachments.*.size' => 'nullable|integer',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'date.required' => 'The date is required.',
            'date.date' => 'The date must be a valid date.',
            'fournisseur_id.required' => 'The supplier is required.',
            'fournisseur_id.exists' => 'The selected supplier does not exist.',
            'products.required' => 'At least one product is required.',
            'products.*.product_id.required' => 'Product selection is required for each item.',
            'products.*.product_id.exists' => 'The selected product does not exist.',
            'products.*.quantity.required' => 'Quantity is required for each product.',
            'products.*.quantity.min' => 'Quantity must be at least 1.',
            'attachments.*.file.max' => 'Each attachment must not exceed 10MB.',
        ];
    }
}
