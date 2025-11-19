<?php

namespace App\Http\Requests\Reception;

use Illuminate\Foundation\Http\FormRequest;

class StoreConventionPrescriptionRequest extends FormRequest
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
            'conventions' => 'required',
            'prise_en_charge_date' => 'required|date',
            'familyAuth' => 'required|string',
            'adherentPatient_id' => 'nullable|exists:patients,id',
            'uploadedFiles' => 'sometimes|array',
            'uploadedFiles.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,gif,bmp,tiff,webp,svg|max:10240',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'conventions.required' => 'The conventions field is required.',
            'prise_en_charge_date.required' => 'The prise en charge date is required.',
            'prise_en_charge_date.date' => 'The prise en charge date must be a valid date.',
            'familyAuth.required' => 'The family authorization is required.',
            'adherentPatient_id.exists' => 'The selected adherent patient is invalid.',
            'uploadedFiles.*.mimes' => 'Uploaded files must be PDF, DOC, DOCX, JPG, JPEG, PNG, GIF, BMP, TIFF, WEBP, or SVG files.',
            'uploadedFiles.*.max' => 'Uploaded files must not exceed 10MB.',
        ];
    }
}