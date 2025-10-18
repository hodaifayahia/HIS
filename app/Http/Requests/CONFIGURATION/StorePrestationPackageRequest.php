<?php

namespace App\Http\Requests\CONFIGURATION;

use Illuminate\Foundation\Http\FormRequest;
class StorePrestationPackageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Assuming user is authenticated to create a package.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Name is nullable as requested.
            'name' => ['nullable', 'string', 'max:255'],
            // Description is nullable.
            'description' => ['nullable', 'string'],
            // Price is nullable and must be a non-negative number.
            'price' => ['nullable', 'numeric', 'min:0'],
            // A list of prestations is required to create a package.
            'prestations' => ['required', 'array'],
            // Each prestation ID in the array must exist in the 'prestations' table.
            'prestations.*' => ['required', 'exists:prestations,id'],
        ];
    }
}
