<?php

namespace App\Http\Requests\INFRASTRUCTURE;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Bed;

class StoreBedRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'room_id' => 'required|exists:rooms,id',
            'bed_identifier' => 'required|string|max:191',
            'status' => 'nullable|in:free,occupied,reserved',
            'current_patient_id' => 'nullable|exists:patients,id'
        ];
    }

    public function messages()
    {
        return [
            'room_id.required' => 'Please select a room for this bed.',
            'room_id.exists' => 'The selected room does not exist.',
            'bed_identifier.required' => 'Bed identifier is required.',
            'bed_identifier.unique' => 'This bed identifier already exists in the selected room.',
        ];
    }
}
