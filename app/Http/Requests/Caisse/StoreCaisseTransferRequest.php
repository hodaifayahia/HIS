<?php
// app/Http/Requests/Caisse/StoreCaisseTransferRequest.php

namespace App\Http\Requests\Caisse;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class StoreCaisseTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'caisse_id' => ['required', 'exists:caisses,id'],
            'to_user_id' => ['required', 'exists:users,id', 'different:from_user_id'],
            'amount_sended' => ['required', 'numeric', 'min:0.01'],
            'direct_transfer' => ['string'],
            'caisse_session_id' => ['required', 'exists:caisse_sessions,id'],
            'have_problems' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:pending,accepted,canceled,done,transferred']
        ];
    }

    public function messages(): array
    {
        return [
            'caisse_id.required' => 'Caisse is required.',
            'caisse_id.exists' => 'Selected caisse does not exist.',
            'from_user_id.required' => 'From user is required.',
            'from_user_id.exists' => 'From user does not exist.',
            'to_user_id.required' => 'To user is required.',
            'to_user_id.exists' => 'To user does not exist.',
            'to_user_id.different' => 'Cannot transfer to the same user.',
            'amount.required' => 'Amount is required.',
            'amount.min' => 'Amount must be greater than 0.',
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
