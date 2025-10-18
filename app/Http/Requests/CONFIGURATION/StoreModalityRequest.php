<?php

namespace App\Http\Requests\CONFIGURATION;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreModalityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check(); // Or add more specific authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'internal_code' => 'required|string|unique:modalities,internal_code',
            'modality_type_id' => 'required|exists:modality_types,id',
            'dicom_ae_title' => 'nullable|string',
            'port' => 'nullable|integer',
            'physical_location_id' => 'nullable|exists:rooms,id',
            'operational_status' => 'required|in:Working,Not Working,In Maintenance',
            'specialization_id' => 'required|exists:specializations,id',
            'integration_protocol' => 'nullable|string',
            'connection_configuration' => 'nullable|string',
            'data_retrieval_method' => 'nullable|string',
            'ip_address' => 'nullable|string',
            'frequency' => 'required|in:Daily,Weekly,Monthly',
            'time_slot_duration' => 'required_if:slot_type,minutes|nullable|integer',
            'slot_type' => 'required|in:minutes,days',
            'booking_window' => 'required_if:slot_type,days|nullable|integer',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
            'include_time' => 'nullable|boolean',
            'consumption_type' => 'nullable|string',
            'consumption_unit' => 'nullable|integer',

            // Appointment Force fields
            'start_time_force' => 'nullable ',
            'end_time_force' => 'nullable |after:start_time_force',
            'number_of_patients' => 'nullable|integer|min:1',

            // Schedules validation
            'schedules' => 'nullable|array',
            'schedules.*.day_of_week' => ['required_with:schedules', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])],
            'schedules.*.shift_period' => 'nullable|in:morning,afternoon,evening',
            'schedules.*.start_time' => 'required_with:schedules ',
            'schedules.*.end_time' => 'required_with:schedules |after:schedules.*.start_time',
            'schedules.*.is_active' => 'nullable|boolean',
            'schedules.*.number_of_patients_per_day' => 'nullable|integer', // Added from update method

            // Custom Dates validation
            'customDates' => 'nullable|array',
            'customDates.*.date' => 'required_with:customDates|date_format:Y-m-d', // Added format
            'customDates.*.shift_period' => 'nullable|in:morning,afternoon,evening',
            'customDates.*.start_time' => 'required_with:customDates ', // Added format
            'customDates.*.end_time' => 'required_with:customDates |after:customDates.*.start_time', // Added format
            'customDates.*.number_of_patients_per_day' => 'nullable|integer', // Added from update method

            // Appointment Booking Window validation
            'appointment_booking_window' => 'nullable|array',
            'appointment_booking_window.*.month' => 'required_with:appointment_booking_window|integer|between:1,12',
            'appointment_booking_window.*.year' => 'required_with:appointment_booking_window|integer',
            'appointment_booking_window.*.is_available' => 'required_with:appointment_booking_window|boolean',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Decode JSON strings if they are sent via FormData
        $this->merge([
            'schedules' => $this->has('schedules') && is_string($this->schedules) ? json_decode($this->schedules, true) : $this->schedules,
            'customDates' => $this->has('customDates') && is_string($this->customDates) ? json_decode($this->customDates, true) : $this->customDates,
            'appointment_booking_window' => $this->has('appointment_booking_window') && is_string($this->appointment_booking_window) ? json_decode($this->appointment_booking_window, true) : $this->appointment_booking_window,
        ]);

        // Handle empty array string for schedules, customDates, and appointment_booking_window
        if ($this->has('schedules') && $this->schedules === '[]') {
            $this->merge(['schedules' => []]);
        }
        if ($this->has('customDates') && $this->customDates === '[]') {
            $this->merge(['customDates' => []]);
        }
        if ($this->has('appointment_booking_window') && $this->appointment_booking_window === '[]') {
            $this->merge(['appointment_booking_window' => []]);
        }
    }
}