<?php

namespace App\Http\Requests\Pharmacy;

use Illuminate\Foundation\Http\FormRequest;

class StoreExternalPrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled in the controller
        // This FormRequest is used to validate the request data
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'nullable|string|max:1000',
        ];
    }
}
