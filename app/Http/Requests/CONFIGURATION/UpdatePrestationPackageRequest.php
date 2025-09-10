<?php

namespace App\Http\Requests\CONFIGURATION;

use Illuminate\Foundation\Http\FormRequest;
class UpdatePrestationPackageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Assuming user is authenticated to update a package.
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
            // All fields are nullable here, as the user might only want to update one.
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            // The list of prestations is also nullable, allowing for updates to other fields without changing the items.
            'prestations' => ['nullable', 'array'],
            'prestations.*' => ['required', 'exists:prestations,id'],
        ];
    }
}