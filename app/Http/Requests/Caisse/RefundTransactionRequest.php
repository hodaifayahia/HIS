<?php
// app/Http/Requests/Caisse/RefundTransactionRequest.php

namespace App\Http\Requests\Caisse;

use Illuminate\Foundation\Http\FormRequest;

class RefundTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'refund_authorization_id' => 'nullable|exists:refund_authorizations,id',
            'refund_amount' => 'required|numeric|min:0.01',
            'fiche_navette_item_id'=>'nullable',
            'fiche_navette_id'=>'nullable',
            'cashier_id' => 'nullable|integer|exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'refund_authorization_id.exists' => 'Refund authorization not found.',
            'refund_amount.required' => 'Refund amount is required.',
            'refund_amount.min' => 'Refund amount must be greater than 0.',
            'cashier_id.exists' => 'Cashier not found.',
            'authorization_or_transaction.required' => 'Either original_transaction_id or refund_authorization_id is required.',
        ];
    }

    protected function prepareForValidation()
    {
        // Normalize keys
        if ($this->has('refund_authorization_id') && $this->input('refund_authorization_id') === '') {
            $this->merge(['refund_authorization_id' => null]);
        }
        if ($this->has('original_transaction_id') && $this->input('original_transaction_id') === '') {
            $this->merge(['original_transaction_id' => null]);
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $original = $this->input('original_transaction_id');
            $auth = $this->input('refund_authorization_id');

            if (empty($original) && empty($auth)) {
                $validator->errors()->add('authorization_or_transaction', 'Either original_transaction_id or refund_authorization_id is required.');
            }
        });
    }
}
