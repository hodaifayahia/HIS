<?php

namespace App\Http\Requests\Reception;

use Illuminate\Foundation\Http\FormRequest;

class StoreFicheNavetteItemsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string|in:prestation,custom',
            'prestations' => 'sometimes|array',
            'prestations.*.id' => 'required|exists:prestations,id',
            'prestations.*.doctor_id' => 'nullable|exists:users,id',
            'prestations.*.quantity' => 'nullable|integer|min:1',
            'prestations.*.isPackage' => 'nullable|boolean',
            'prestations.*.convention_id' => 'nullable|exists:conventions,id',
            'prestations.*.convention_price' => 'nullable|numeric',
            'prestations.*.uses_convention' => 'nullable|boolean',
            'dependencies' => 'sometimes|array',
            'dependencies.*.id' => 'required|exists:prestations,id',
            'dependencies.*.doctor_id' => 'nullable|exists:users,id',
            'dependencies.*.convention_id' => 'nullable|exists:conventions,id',
            'dependencies.*.convention_price' => 'nullable|numeric',
            'dependencies.*.uses_convention' => 'nullable|boolean',
            'customPrestations' => 'sometimes|array',
            'customPrestations.*.id' => 'nullable|exists:prestations,id',
            'customPrestations.*.doctor_id' => 'nullable|exists:users,id',
            'customPrestations.*.selected_doctor_id' => 'nullable|exists:users,id',
            'customPrestations.*.quantity' => 'nullable|integer|min:1',
            'customPrestations.*.display_name' => 'nullable|string',
            'customPrestations.*.type' => 'nullable|string|in:predefined,custom',
            'customPrestations.*.convention_id' => 'nullable|exists:conventions,id',
            'customPrestations.*.convention_price' => 'nullable|numeric',
            'customPrestations.*.uses_convention' => 'nullable|boolean',
            'selectedSpecialization' => 'nullable|exists:specializations,id',
            'selectedDoctor' => 'nullable|exists:users,id',
            'selectedConventions' => 'sometimes|array',
            'selectedConventions.*' => 'exists:conventions,id',
            'selectedOrganismes' => 'sometimes|array',
            'selectedOrganismes.*' => 'exists:companies,id',
            'enableConventionMode' => 'nullable|boolean',
            'prise_en_charge_date' => 'nullable|date',
            'familyAuth' => 'nullable|array',
            'familyAuth.*' => 'string|in:ascendant,descendant,Conjoint,adherent,autre',
            'notes' => 'nullable|string',
            'packages.*.id' => 'required|exists:prestation_packages,id',
            'packages.*.prestations' => 'sometimes|array',
            'packages.*.prestations.*.id' => 'required|exists:prestations,id',
            'uploadedFiles' => 'sometimes|array',
            'uploadedFiles.*.name' => 'sometimes|string',
            'uploadedFiles.*.size' => 'sometimes|integer',
            'uploadedFiles.*.type' => 'sometimes|string',
            'uploadedFiles.*.file' => 'sometimes|file|mimes:pdf,doc,docx|max:10240',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'The type field is required.',
            'type.in' => 'The type must be either "prestation" or "custom".',
            'prestations.*.id.exists' => 'The selected prestations are invalid.',
            'prestations.*.doctor_id.exists' => 'The selected doctor is invalid.',
            'prestations.*.quantity.integer' => 'The quantity must be an integer.',
            'prestations.*.quantity.min' => 'The quantity must be at least 1.',
            'dependencies.*.id.exists' => 'The selected dependencies are invalid.',
            'dependencies.*.doctor_id.exists' => 'The selected dependency doctor is invalid.',
            'customPrestations.*.id.exists' => 'The selected custom prestations are invalid.',
            'customPrestations.*.doctor_id.exists' => 'The selected custom doctor is invalid.',
            'customPrestations.*.selected_doctor_id.exists' => 'The selected custom doctor is invalid.',
            'customPrestations.*.quantity.integer' => 'The quantity must be an integer.',
            'customPrestations.*.quantity.min' => 'The quantity must be at least 1.',
            'selectedSpecialization.exists' => 'The selected specialization is invalid.',
            'selectedDoctor.exists' => 'The selected doctor is invalid.',
            'selectedConventions.*.exists' => 'The selected conventions are invalid.',
            'selectedOrganismes.*.exists' => 'The selected organismes are invalid.',
            'prise_en_charge_date.date' => 'The prise en charge date must be a valid date.',
            'familyAuth.*.in' => 'The family authorization must be one of: ascendant, descendant, Conjoint, adherent, autre.',
            'packages.*.id.exists' => 'The selected packages are invalid.',
            'packages.*.prestations.*.id.exists' => 'The selected package prestations are invalid.',
            'uploadedFiles.*.file.mimes' => 'Uploaded files must be PDF, DOC, or DOCX files.',
            'uploadedFiles.*.file.max' => 'Uploaded files must not exceed 10MB.',
        ];
    }
}