<?php

namespace App\Http\Requests\B2B;


use Illuminate\Foundation\Http\FormRequest;

class StoreConventionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organisme_id' => 'required|exists:organismes,id', // Assuming you have an organismes table
            'name' => 'required|string|max:255',
            'is_general' => 'nullable|boolean',
            'status' => 'required|string|in:active,inactive,pending', // Adjust values as needed
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date|after:today', // Ensure end date is after start date and not in the past
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0|gte:min_price',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'family_auth' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'organisme_id.required' => 'Organisme is required',
            'organisme_id.exists' => 'Selected organisme does not exist',
            'name.required' => 'Convention name is required',
            'is_general.nullable' => 'General status is nullable',
            'status.required' => 'Status is required',
            'start_date.required' => 'Start date is required',
            'end_date.required' => 'End date is required',
            'end_date.after' => 'End date must be after start date',
            'max_price.gte' => 'Max price must be greater than or equal to min price',
            'discount_percentage.min' => 'Discount percentage must be between 0 and 100',
            'discount_percentage.max' => 'Discount percentage must be between 0 and 100',
        ];
    }
}
