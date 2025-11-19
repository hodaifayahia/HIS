<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsignmentReceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Adjust based on your authorization logic
    }

    public function rules(): array
    {
        return [
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'reception_date' => 'required|date',
            'unit_of_measure' => 'nullable|string|max:50',
            'origin_note' => 'nullable|string|max:1000',
            'reception_type' => 'nullable|string|in:consignment,purchase,return',
            'operation_type' => 'nullable|string|in:manual,automatic',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.pharmacy_product_id' => 'nullable|exists:pharmacy_products,id',
            'items.*.product_type' => 'required|in:stock,pharmacy',
            'items.*.quantity_received' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ];
    }

    /**
     * Validate that each item has either product_id (stock) or pharmacy_product_id (pharmacy)
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);

            foreach ($items as $index => $item) {
                $product_id = $item['product_id'] ?? null;
                $pharmacy_product_id = $item['pharmacy_product_id'] ?? null;
                $product_type = $item['product_type'] ?? null;

                // Validate product_type is specified
                if (! $product_type) {
                    $validator->errors()->add(
                        "items.{$index}.product_type",
                        'Product type (stock or pharmacy) is required'
                    );

                    continue;
                }

                // Validate stock products have product_id
                if ($product_type === 'stock' && ! $product_id) {
                    $validator->errors()->add(
                        "items.{$index}.product_id",
                        'Product is required for stock items'
                    );
                }

                // Validate pharmacy products have pharmacy_product_id
                if ($product_type === 'pharmacy' && ! $pharmacy_product_id) {
                    $validator->errors()->add(
                        "items.{$index}.pharmacy_product_id",
                        'Pharmacy product is required for pharmacy items'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'fournisseur_id.required' => 'Supplier is required',
            'fournisseur_id.exists' => 'Selected supplier does not exist',
            'reception_date.required' => 'Reception date is required',
            'items.required' => 'At least one item is required',
            'items.min' => 'At least one item is required',
            'items.*.product_id.exists' => 'Selected stock product does not exist',
            'items.*.pharmacy_product_id.exists' => 'Selected pharmacy product does not exist',
            'items.*.product_type.required' => 'Product type is required for all items',
            'items.*.product_type.in' => 'Product type must be either "stock" or "pharmacy"',
            'items.*.quantity_received.required' => 'Quantity is required for all items',
            'items.*.quantity_received.min' => 'Quantity must be at least 1',
            'items.*.unit_price.required' => 'Unit price is required for all items',
            'items.*.unit_price.min' => 'Unit price cannot be negative',
        ];
    }
}
