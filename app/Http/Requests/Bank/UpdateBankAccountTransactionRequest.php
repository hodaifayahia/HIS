<?php
// app/Http/Requests/Bank/UpdateBankAccountTransactionRequest.php

namespace App\Http\Requests\Bank;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateBankAccountTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $transaction = $this->route('bank_account_transaction');

        return [
            'bank_account_id' => ['sometimes', 'exists:bank_accounts,id'],
            'accepted_by_user_id' => ['sometimes', 'exists:users,id'],
            'transaction_type' => ['sometimes', 'in:credit,debit'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'transaction_date' => ['sometimes', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'reference' => [
                'sometimes', 
                'string', 
                'max:255', 
                Rule::unique('bank_account_transactions', 'reference')->ignore($transaction->id)
            ],
            'status' => ['sometimes', 'in:pending,completed,cancelled,reconciled'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'The provided data is invalid.',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
