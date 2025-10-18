<?php

namespace App\Http\Requests\Caisse;

use Illuminate\Foundation\Http\FormRequest;

class BulkPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fiche_navette_id' => 'required|integer|exists:fiche_navettes,id',
            'caisse_session_id' => 'nullable|integer',
            'cashier_id' => 'nullable|integer|exists:users,id',
            'patient_id' => 'required|integer|exists:patients,id',
            'payment_method' => 'required|string|in:cash,card,cheque,transfer',
            'transaction_type' => 'required|string|in:bulk_payment',
            'total_amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',

            // Items array validation
            'items' => 'required|array|min:1',
            'items.*.fiche_navette_item_id' => 'required|integer|exists:fiche_navette_items,id',
            'items.*.item_dependency_id' => 'nullable|integer|exists:item_dependencies,id',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.remaining_amount' => 'required|numeric|min:0',
            'items.*.item_name' => 'nullable|string|max:255',
            'items.*.is_dependency' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'fiche_navette_id.required' => 'Fiche navette ID is required',
            'fiche_navette_id.exists' => 'Fiche navette not found',
            'cashier_id.exists' => 'Cashier not found',
            'patient_id.required' => 'Patient ID is required',
            'patient_id.exists' => 'Patient not found',
            'payment_method.required' => 'Payment method is required',
            'payment_method.in' => 'Invalid payment method',
            'total_amount.required' => 'Total amount is required',
            'total_amount.min' => 'Total amount must be greater than 0',
            'items.required' => 'Items array is required',
            'items.min' => 'At least one item is required',
            'items.*.fiche_navette_item_id.required' => 'Item ID is required for each item',
            'items.*.fiche_navette_item_id.exists' => 'Item not found',
            'items.*.amount.required' => 'Amount is required for each item',
            'items.*.amount.min' => 'Amount must be greater than 0 for each item',
        ];
    }
}
