<?php

namespace App\Http\Requests\B2B;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AnnexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'annex_name' => ['required', 'string', 'max:255'],
            'convention_id' => ['sometimes', 'required', 'exists:conventions,id'],
            'service_id' => ['required', 'exists:services,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'min_price' => ['nullable', 'integer', 'min:0'], // Changed to integer since DB is int(11)
            'prestation_prix_status' => ['required', 'string', 'in:convenience_prix,empty,public_prix'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'min_price.integer' => 'The minimum price must be a valid integer.',
            'min_price.min' => 'The minimum price must be at least 0.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Convert empty strings to null for numeric fields
        $this->merge([
            'min_price' => $this->min_price === '' || $this->min_price === 'null' ? null : $this->min_price,
        ]);
    }
}
