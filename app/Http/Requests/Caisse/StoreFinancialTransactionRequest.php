<?php
// app/Http/Requests/Caisse/StoreFinancialTransactionRequest.php

namespace App\Http\Requests\Caisse;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinancialTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fiche_navette_id' => 'nullable|integer|exists:fiche_navette,id',
            'caisse_session_id' => 'nullable|integer',
            'fiche_navette_item_id' => 'required|integer',
            'item_dependency_id' => 'nullable|integer|exists:item_dependencies,id',
            'patient_id' => 'required|integer',
            'cashier_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'transaction_type' => 'required|string|in:payment,refund,donation,credit,adjustment',
            'payment_method' => 'required|string|in:cash,card,cheque,transfer,other',
            'notes' => 'nullable|string|max:500',
            'dependent_prestation_id' => 'nullable|integer',
            'items' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.exists' => 'The selected patient does not exist.',
            'fiche_navette_item_id.exists' => 'The selected fiche navette item does not exist.',
            'item_dependency_id.exists' => 'The selected item dependency does not exist.',
            'cashier_id.exists' => 'The selected cashier does not exist.',
        ];
    }
}
