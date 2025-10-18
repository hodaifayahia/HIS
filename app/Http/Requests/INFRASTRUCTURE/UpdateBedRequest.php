<?php


namespace App\Http\Requests\INFRASTRUCTURE;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBedRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'room_id' => 'required|exists:rooms,id',
            'bed_identifier' => [
                'required',
                'string',
                'max:191',
                Rule::unique('beds')->ignore($this->bed)->where(function ($query) {
                    return $query->where('room_id', $this->room_id);
                })
            ],
            'status' => 'nullable|in:free,occupied,reserved',
            'current_patient_id' => 'nullable|exists:patients,id'
        ];
    }
}
