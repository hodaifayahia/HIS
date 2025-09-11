<?php
// app/Http/Requests/Caisse/AcceptCaisseTransferRequest.php

namespace App\Http\Requests\Caisse;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class AcceptCaisseTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount_received' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'transfer_token.required' => 'Transfer token is required.',
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
