<?php

namespace App\Http\Requests\Reception\ApproverRemise;

use Illuminate\Foundation\Http\FormRequest;

class StoreRemiseApproverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add policy check if needed
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'approver_id' => ['required', 'integer', 'exists:users,id', 'different:user_id'],
            'is_approved' => ['sometimes', 'boolean'],
            'comments' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'approver_id.different' => 'User cannot approve themselves.',
            'user_id.exists' => 'Selected user does not exist.',
            'approver_id.exists' => 'Selected approver does not exist.',
        ];
    }
}
