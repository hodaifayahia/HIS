<?php

namespace App\Http\Requests\CONFIGURATION;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RemiseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Change to true or implement your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $remiseId = $this->route('remise') ? $this->route('remise')->id : null;

        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('remises', 'code')->ignore($remiseId)
            ],
            'prestation_id' => 'nullable|exists:prestations,id',
            'amount' => 'nullable|numeric|min:0',
            'percentage' => 'nullable|numeric|min:0|max:100',
            'type' => 'required|string|in:fixed,percentage',
            'is_active' => 'boolean',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'prestation_ids' => 'nullable|array',
            'prestation_ids.*' => 'exists:prestations,id'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Ensure either amount or percentage is provided based on type
            if ($this->type === 'fixed' && empty($this->amount)) {
                $validator->errors()->add('amount', 'Amount is required for fixed type remise.');
            }

            if ($this->type === 'percentage' && empty($this->percentage)) {
                $validator->errors()->add('percentage', 'Percentage is required for percentage type remise.');
            }

            // Ensure only one of amount or percentage is set
            if (!empty($this->amount) && !empty($this->percentage)) {
                $validator->errors()->add('amount', 'Cannot set both amount and percentage.');
                $validator->errors()->add('percentage', 'Cannot set both amount and percentage.');
            }
        });
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The remise name is required.',
            'code.required' => 'The remise code is required.',
            'code.unique' => 'This remise code is already taken.',
            'type.required' => 'The remise type is required.',
            'type.in' => 'The remise type must be either fixed or percentage.',
            'amount.numeric' => 'The amount must be a valid number.',
            'amount.min' => 'The amount must be greater than or equal to 0.',
            'percentage.numeric' => 'The percentage must be a valid number.',
            'percentage.min' => 'The percentage must be greater than or equal to 0.',
            'percentage.max' => 'The percentage cannot be greater than 100.',
            'user_ids.array' => 'User IDs must be an array.',
            'user_ids.*.exists' => 'One or more selected users do not exist.',
            'prestation_ids.array' => 'Prestation IDs must be an array.',
            'prestation_ids.*.exists' => 'One or more selected prestations do not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'user_ids' => 'users',
            'prestation_ids' => 'prestations',
        ];
    }
}
