<?php
// app/Http/Requests/Coffre/StoreCaisseSessionRequest.php

namespace App\Http\Requests\Coffre;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class StoreCaisseSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'caisse_id' => ['required', 'exists:caisses,id'],
            'user_id' => ['required', 'exists:users,id'],
            'coffre_id_source' => ['nullable', 'exists:coffres,id'],
            'opening_amount' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'opening_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'caisse_id.required' => 'The cash register is required.',
            'caisse_id.exists' => 'The selected cash register does not exist.',
            'user_id.required' => 'The user is required.',
            'user_id.exists' => 'The selected user does not exist.',
            'coffre_id_source.exists' => 'The selected source safe does not exist.',
            'opening_amount.required' => 'The opening amount is required.',
            'opening_amount.numeric' => 'The opening amount must be a number.',
            'opening_amount.min' => 'The opening amount cannot be negative.',
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
