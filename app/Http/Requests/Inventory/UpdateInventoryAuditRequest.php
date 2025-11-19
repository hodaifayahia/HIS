<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'scheduled_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'in:draft,in_progress,completed,cancelled'],
            'participants' => ['nullable', 'array'],
            'participants.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'participants.*.is_participant' => ['nullable', 'boolean'],
            'participants.*.is_able_to_see' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Audit name is required',
            'name.max' => 'Audit name cannot exceed 255 characters',
            'status.in' => 'Invalid status value',
            'participants.*.user_id.required' => 'Each participant must have a user ID',
            'participants.*.user_id.exists' => 'Selected user does not exist',
        ];
    }
}
