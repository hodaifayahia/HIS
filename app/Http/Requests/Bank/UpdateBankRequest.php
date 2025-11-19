<?php
// app/Http/Requests/Bank/UpdateBankRequest.php

namespace App\Http\Requests\Bank;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Always true as requested
    }

    public function rules(): array
    {
        $bank = $this->route('bank');

        return [
            'name' => [
                'sometimes', 
                'string', 
                'max:255', 
                Rule::unique('banks', 'name')->ignore($bank->id)
            ],
            'code' => [
                'sometimes', 
                'nullable', 
                'string', 
                'max:10', 
                Rule::unique('banks', 'code')->ignore($bank->id)
            ],
            'swift_code' => ['sometimes', 'nullable', 'string', 'max:11'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'logo_url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'supported_currencies' => ['sometimes', 'nullable', 'array'],
            'supported_currencies.*' => ['string', 'in:DZD,EUR,USD,GBP,CHF,JPY'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
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
