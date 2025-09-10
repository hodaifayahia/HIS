<?php

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganismeUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Set this to true if you have authorization logic (e.g., checking user roles/permissions)
        // For now, we'll set it to true to allow all requests to proceed to validation.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // When updating, we need to ignore the current organisme's ID for unique checks.
        // The 'organisme' parameter comes from route model binding.
        $organismeId = $this->route('organisme') ? $this->route('organisme')->id : null;

        return [
            'name' => 'required|string|max:255',
                    'organism_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/'],

            'legal_form' => 'nullable|string|max:255',
            'trade_register_number' => ['nullable', 'string', 'max:255', Rule::unique('organismes')->ignore($organismeId)],
            'tax_id_nif' => ['nullable', 'string', 'max:255', Rule::unique('organismes')->ignore($organismeId)],
            'statistical_id' => ['nullable', 'string', 'max:255', Rule::unique('organismes')->ignore($organismeId)],
            'article_number' => 'nullable|string|max:255',
            'wilaya' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'fax' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('organismes')->ignore($organismeId)],
            'website' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'initial_invoice_number' => 'nullable|string|max:255',
            'initial_credit_note_number' => 'nullable|string|max:255',
            'logo_url' => 'nullable|string|url|max:255',
            'profile_image_url' => 'nullable|string|url|max:255',
            'description' => 'nullable|string',
            'industry' => 'nullable|string|max:255',
            'creation_date' => 'nullable|date',
            'number_of_employees' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Another organisme already uses this email address.',
            'trade_register_number.unique' => 'The trade register number is already taken by another organisme.',
            // Add more custom messages as needed
        ];
    }
}