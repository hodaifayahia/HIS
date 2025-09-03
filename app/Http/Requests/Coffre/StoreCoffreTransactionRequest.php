<?php

namespace App\Http\Requests\Coffre;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class StoreCoffreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_type' => 'required|string|in:deposit,withdraw',
            'coffre_id' => 'required|exists:coffres,id',
            'dest_coffre_id' => 'nullable|exists:coffres,id|different:coffre_id', // Optional for transfers
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:1000'
            // user_id removed - will be set automatically from auth
        ];
    }

    public function messages(): array
    {
        return [
            'coffre_id.required' => 'The safe is required.',
            'coffre_id.exists' => 'The selected safe does not exist.',
            'transaction_type.required' => 'The transaction type is required.',
            'transaction_type.in' => 'Invalid transaction type.',
            'amount.required' => 'The amount is required.',
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