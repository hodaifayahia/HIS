<?php
// app/Http/Requests/Bank/StoreBankRequest.php

namespace App\Http\Requests\Bank;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class StoreBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Always true as requested
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:banks,name'],
            'code' => ['nullable', 'string', 'max:10', 'unique:banks,code'],
            'swift_code' => ['nullable', 'string', 'max:11'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'logo_url' => ['nullable', 'url', 'max:255'],
            'supported_currencies' => ['nullable', 'array'],
            'supported_currencies.*' => ['string', 'in:DZD,EUR,USD,GBP,CHF,JPY'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The bank name is required.',
            'name.unique' => 'This bank name already exists.',
            'code.unique' => 'This bank code already exists.',
            'email.email' => 'Please enter a valid email address.',
            'website.url' => 'Please enter a valid website URL.',
            'supported_currencies.*.in' => 'Invalid currency code.',
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
