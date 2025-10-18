<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApprovalPersonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $approvalPersonId = $this->route('approval_person') ?? $this->route('id');

        return [
            'user_id' => [
                'sometimes',
                'required',
                'exists:users,id',
                Rule::unique('approval_persons', 'user_id')->ignore($approvalPersonId),
            ],
            'max_amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'priority' => ['integer', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Please select a user.',
            'user_id.exists' => 'The selected user does not exist.',
            'user_id.unique' => 'This user is already an approval person.',
            'max_amount.required' => 'Maximum amount is required.',
            'max_amount.numeric' => 'Maximum amount must be a number.',
            'max_amount.min' => 'Maximum amount must be at least 0.',
        ];
    }
}
