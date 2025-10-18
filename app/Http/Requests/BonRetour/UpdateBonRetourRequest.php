<?php

namespace App\Http\Requests\BonRetour;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBonRetourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fournisseur_id' => ['sometimes', 'exists:fournisseurs,id'],
            'return_type' => ['sometimes', Rule::in(['defective', 'expired', 'wrong_delivery', 'overstock', 'quality_issue', 'other'])],
            'service_id' => ['nullable', 'string', 'max:50'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'return_date' => ['nullable', 'date'],
            'reference_invoice' => ['nullable', 'string', 'max:100'],
            'credit_note_received' => ['boolean'],
            'credit_note_number' => ['nullable', 'required_if:credit_note_received,true', 'string', 'max:100'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['string'],
            
            // Items
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['sometimes', 'exists:bon_retour_items,id'],
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
}
