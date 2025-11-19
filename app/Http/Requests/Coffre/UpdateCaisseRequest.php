<?php
// app/Http/Requests/Coffre/UpdateCaisseRequest.php

namespace App\Http\Requests\Coffre;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateCaisseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $caisse =true;

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'service_id' => ['nullable', 'exists:services,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A cash register with this name already exists in the selected service.',
            'service_id.exists' => 'The selected service does not exist.',
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
