<?php

namespace App\Http\Requests\Purchsing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBonReceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'received_by' => 'required|exists:users,id',
            'date_reception' => 'required|date',
            'bon_livraison_numero' => 'nullable|string|max:255',
            'bon_livraison_date' => 'nullable|date',
            'facture_numero' => 'nullable|string|max:255',
            'facture_date' => 'nullable|date',
            'nombre_colis' => 'nullable|integer|min:0',
            'observation' => 'nullable|string',

            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:bon_reception_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_received' => 'required|integer|min:0',
            'items.*.unit_price' => 'nullable|numeric',

            'attachments' => 'nullable|array',
            'attachments.*.file' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240',
            'attachments.*.name' => 'nullable|string|max:255',
            'attachments.*.description' => 'nullable|string',
        ];
    }
}
