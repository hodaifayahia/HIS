<?php

namespace App\Http\Requests\Coffre;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class UpdateCoffreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_type' => 'sometimes|required|string|in:deposit,withdraw',
            'coffre_id' => 'sometimes|required|exists:coffres,id',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'description' => 'nullable|string|max:1000'
            // user_id removed
        ];
    }

    public function messages(): array
    {
        return [
            'coffre_id.exists' => 'The selected safe does not exist.',
            'transaction_type.in' => 'Invalid transaction type.',
            'amount.numeric' => 'The amount must be a number.',
            'amount.min' => 'The amount must be greater than 0.',
            'amount.max' => 'The amount is too large.',
            'dest_coffre_id.different' => 'Destination safe must be different from source safe.',
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