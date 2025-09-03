<?php
// app/Http/Requests/Coffre/StoreCaisseRequest.php

namespace App\Http\Requests\Coffre;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class StoreCaisseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 
                'string', 
                'max:255',
                'unique:caisses,name,NULL,id,service_id,' . $this->service_id
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'service_id' => ['nullable', 'exists:services,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The cash register name is required.',
            'name.unique' => 'A cash register with this name already exists in the selected service.',
            'service_id.required' => 'The service is required.',
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
