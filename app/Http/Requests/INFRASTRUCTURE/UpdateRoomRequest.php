<?php

namespace App\Http\Requests\INFRASTRUCTURE;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Set to true if you want to allow all users to update rooms
        // Otherwise, add your authorization logic here (e.g., check user roles/permissions)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        // Get the room ID from the route parameters
        $roomId = $this->route('room'); // Assuming your route parameter is named 'room'

        return [
            'name' => 'required|string|max:255',
            // Allow room_number to be unique except for the current room being updated
            'room_number' => ['required', 'string', 'max:255', Rule::unique('rooms')->ignore($roomId)],
            'location' => 'required|string|max:255',
            'status' => ['required', 'string', Rule::in(['available', 'occupied', 'maintenance', 'reserved'])],
            'room_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // For file uploads
            'room_type_id' => 'required|exists:room_types,id',
            'pavilion_id' => 'nullable',
            'service_id' => 'required|exists:services,id',
            'number_of_people' => 'required|numeric|min:1'
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'room_number.unique' => 'The room number has already been taken.',
            'nightly_price.min' => 'The nightly price must be at least 0.01.',
            'room_image.mimes' => 'The room image must be a file of type: jpeg, png, jpg, gif, svg.',
            'room_image.max' => 'The room image may not be greater than 2MB.',
        ];
    }
}
