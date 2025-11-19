<?php

namespace App\Http\Requests\Configuration;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransferApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole(['admin', 'SuperAdmin']);
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'sometimes',
                'integer',
                Rule::exists('users', 'id')
            ],
            'maximum' => [
                'sometimes',
                'numeric',
            ],
            'is_active' => [
                'sometimes',
                'boolean'
            ],
            'note' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists' => 'Selected user does not exist.',
            'maximum.min' => 'Maximum amount must be greater than 0.',
            'maximum.max' => 'Maximum amount is too large.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }
}
