<?php
// app/Http/Requests/Caisse/OverpaymentHandlingRequest.php

namespace App\Http\Requests\Caisse;

use Illuminate\Foundation\Http\FormRequest;

class OverpaymentHandlingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fiche_navette_item_id' => 'required|exists:fiche_navette_items,id',
            'patient_id' => 'required|integer',
            'cashier_id' => 'nullable|integer|exists:users,id',
            'required_amount' => 'required|numeric|min:0.01',
            'paid_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,card,check,transfer,insurance',
            'overpayment_action' => 'required|in:donate,balance',
            'notes' => 'nullable|string|max:1000',
            'item_dependency_id' => 'nullable|exists:item_dependencies,id',
            'dependent_prestation_id' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'overpayment_action.required' => 'Please specify how to handle the overpayment.',
            'overpayment_action.in' => 'Overpayment action must be either donate or balance.',
            'cashier_id.exists' => 'Cashier not found',
        ];
    }
}
