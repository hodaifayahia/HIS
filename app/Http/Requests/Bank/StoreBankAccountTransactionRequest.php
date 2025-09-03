<?php
// app/Http/Requests/Bank/StoreBankAccountTransactionRequest.php

namespace App\Http\Requests\Bank;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class StoreBankAccountTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'accepted_by_user_id' => ['required', 'exists:users,id'],
            'transaction_type' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'reference' => ['nullable', 'string', 'max:255', 'unique:bank_account_transactions,reference'],
            'status' => ['nullable', 'in:pending,completed,cancelled,reconciled'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_account_id.required' => 'Bank account is required.',
            'bank_account_id.exists' => 'Selected bank account does not exist.',
            'accepted_by_user_id.required' => 'User is required.',
            'accepted_by_user_id.exists' => 'Selected user does not exist.',
            'transaction_type.required' => 'Transaction type is required.',
            'transaction_type.in' => 'Transaction type must be credit or debit.',
            'amount.required' => 'Amount is required.',
            'amount.min' => 'Amount must be greater than 0.',
            'transaction_date.required' => 'Transaction date is required.',
            'reference.unique' => 'This reference number already exists.',
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
