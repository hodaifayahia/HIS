<?php

namespace App\Http\Requests\Configuration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransferApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole(['admin', 'SuperAdmin']);
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
            ],
            'maximum' => [
                'required',
                'numeric',
            ],
            'is_active' => [
                'nullable',
                'boolean'
            ],
            'note' => [
                'nullable',
                'string',
                'max:1000'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User is required.',
            'user_id.exists' => 'Selected user does not exist.',
            'maximum.required' => 'Maximum transfer amount is required.',
            'maximum.min' => 'Maximum amount must be greater than 0.',
            'maximum.max' => 'Maximum amount is too large.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }
}