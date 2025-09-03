<?php

namespace App\Http\Requests\Reception;

use Illuminate\Foundation\Http\FormRequest;

class UploadConventionFilesRequest extends FormRequest
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
            'files' => 'required|array',
            'files.*' => 'file|mimes:pdf,doc,docx|max:10240',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'files.required' => 'The files field is required.',
            'files.array' => 'The files must be an array.',
            'files.*.mimes' => 'Uploaded files must be PDF, DOC, or DOCX files.',
            'files.*.max' => 'Uploaded files must not exceed 10MB.',
        ];
    }
}