<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalleUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // You can add authorization logic here if needed
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $salleId = $this->route('salle')->id;

        return [
            'name' => 'required|string|max:255',
            'number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('salls', 'number')->ignore($salleId),
            ],
            'description' => 'nullable|string|max:1000',
            'defult_specialization_id' => 'nullable|integer|exists:specializations,id',
            'specialization_ids' => 'nullable|array',
            'specialization_ids.*' => 'integer|exists:specializations,id',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'salle name',
            'number' => 'salle number',
            'description' => 'description',
            'defult_specialization_id' => 'default specialization',
            'specialization_ids' => 'specializations',
            'specialization_ids.*' => 'specialization',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The salle name is required.',
            'name.string' => 'The salle name must be a valid string.',
            'name.max' => 'The salle name may not be greater than 255 characters.',

            'number.required' => 'The salle number is required.',
            'number.string' => 'The salle number must be a valid string.',
            'number.max' => 'The salle number may not be greater than 50 characters.',
            'number.unique' => 'This salle number is already taken.',

            'description.string' => 'The description must be a valid string.',
            'description.max' => 'The description may not be greater than 1000 characters.',

            'defult_specialization_id.integer' => 'The default specialization must be a valid number.',
            'defult_specialization_id.exists' => 'The selected default specialization does not exist.',

            'specialization_ids.array' => 'The specializations must be provided as a list.',
            'specialization_ids.*.integer' => 'Each specialization ID must be a valid number.',
            'specialization_ids.*.exists' => 'One or more selected specializations do not exist.',
        ];
    }
}
