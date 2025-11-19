<?php

namespace App\Http\Requests\MANAGER;

use Illuminate\Foundation\Http\FormRequest;

class PatientTrackingCheckOutRequest extends FormRequest
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
            'status' => 'nullable|string|in:completed,cancelled',
            'notes' => 'nullable|string|max:1000',
            'update_item_status' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'status' => 'status',
            'notes' => 'notes',
        ];
    }
}
