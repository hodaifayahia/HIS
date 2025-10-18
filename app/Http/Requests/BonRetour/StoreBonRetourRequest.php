<?php

namespace App\Http\Requests\BonRetour;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBonRetourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bon_entree_id' => ['nullable', 'exists:bon_entrees,id'],
            'fournisseur_id' => ['required', 'exists:fournisseurs,id'],
            'return_type' => ['required', Rule::in(['defective', 'expired', 'wrong_delivery', 'overstock', 'quality_issue', 'other'])],
            'status' => ['sometimes', Rule::in(['draft', 'pending'])],
            'service_id' => ['nullable', 'exists:services,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'return_date' => ['nullable', 'date'],
            'reference_invoice' => ['nullable', 'string', 'max:100'],
            'credit_note_received' => ['boolean'],
            'credit_note_number' => ['nullable', 'required_if:credit_note_received,true', 'string', 'max:100'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['string'],
            
            // Items
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.bon_entree_item_id' => ['nullable', 'exists:bon_entree_items,id'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.serial_number' => ['nullable', 'string', 'max:100'],
            'items.*.expiry_date' => ['nullable', 'date'],
            'items.*.quantity_returned' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tva' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.return_reason' => ['required', Rule::in(['defective', 'expired', 'damaged', 'wrong_item', 'quality_issue', 'other', 'overstock'])],
            'items.*.remarks' => ['nullable', 'string', 'max:500'],
            'items.*.storage_location' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'fournisseur_id.required' => 'The supplier is required.',
            'fournisseur_id.exists' => 'The selected supplier does not exist.',
            'return_type.required' => 'The return type is required.',
            'items.required' => 'At least one item is required.',
            'items.*.product_id.required' => 'Each item must have a product.',
            'items.*.quantity_returned.required' => 'Quantity is required for each item.',
            'items.*.quantity_returned.min' => 'Quantity must be at least 1.',
            'items.*.unit_price.required' => 'Unit price is required for each item.',
            'items.*.return_reason.required' => 'Return reason is required for each item.',
        ];
    }
}
