<?php
// app/Http/Requests/Coffre/CloseCaisseSessionRequest.php

namespace App\Http\Requests\Coffre;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class CloseCaisseSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'closing_amount' => ['nullable', 'numeric', 'min:0'],
            'expected_closing_amount' => ['nullable', 'numeric', 'min:0'],
            'coffre_id_destination' => ['nullable', 'exists:coffres,id'],
            'closing_notes' => ['nullable', 'string', 'max:1000'],
            
            // Denominations validation
            'denominations' => ['nullable', 'array'],
            'denominations.*.type' => ['required_with:denominations', 'in:coin,note'],
            'denominations.*.value' => ['required_with:denominations', 'numeric', 'min:0'],
            'denominations.*.quantity' => ['required_with:denominations', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'closing_amount.required' => 'The closing amount is required.',
            'closing_amount.numeric' => 'The closing amount must be a number.',
            'coffre_id_destination.exists' => 'The selected destination safe does not exist.',
            'denominations.*.type.in' => 'Denomination type must be coin or note.',
            'denominations.*.value.numeric' => 'Denomination value must be a number.',
            'denominations.*.quantity.integer' => 'Quantity must be a whole number.',
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
