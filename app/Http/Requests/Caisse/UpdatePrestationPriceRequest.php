<?php
// app/Http/Requests/Caisse/UpdatePrestationPriceRequest.php

namespace App\Http\Requests\Caisse;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrestationPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prestation_id' => 'required|integer',
            'fiche_navette_item_id' => 'required|exists:fiche_navette_items,id',
            'new_final_price' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
        ];
    }
}
