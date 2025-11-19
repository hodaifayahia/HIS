<?php
// app/Http/Requests/Bank/StoreBankAccountRequest.php

namespace App\Http\Requests\Bank;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Services\Bank\BankAccountService;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_id' => ['required', 'integer', 'exists:banks,id'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50', 'unique:bank_accounts,account_number'],
            'iban' => ['nullable', 'string', 'max:34', 'regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]{4}[0-9]{7}([A-Z0-9]?){0,16}$/'],
            'swift_bic' => ['nullable', 'string', 'max:11', 'regex:/^[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?$/'],
            'currency' => ['required', 'string', 'size:3', 'in:DZD,EUR,USD,GBP,CHF,JPY'],
            'current_balance' => ['nullable', 'numeric', 'min:0'],
            'available_balance' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_id.required' => 'The bank is required.',
            'bank_id.exists' => 'The selected bank does not exist.',
            'account_name.required' => 'The account name is required.',
            'account_number.required' => 'The account number is required.',
            'account_number.unique' => 'This account number already exists.',
            'iban.regex' => 'The IBAN format is invalid.',
            'swift_bic.regex' => 'The SWIFT/BIC format is invalid.',
            'currency.required' => 'The currency is required.',
            'currency.size' => 'Currency must be exactly 3 characters.',
            'currency.in' => 'The selected currency is not supported.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $bankAccountService = app(BankAccountService::class);
            $errors = $bankAccountService->validateBankAccountData($this->validated());
            
            foreach ($errors as $field => $message) {
                $validator->errors()->add($field, $message);
            }
        });
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
