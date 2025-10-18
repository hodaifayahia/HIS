<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DoctorEmergencyPlanningRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add proper authorization logic as needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $planningId = $this->route('planning') ? $this->route('planning')->id : null;

        return [
            'doctor_id' => 'required|exists:doctors,id',
            'service_id' => 'required|exists:services,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:'.now()->year,
            'planning_date' => 'required|date|after_or_equal:today',
            'shift_start_time' => 'required|date_format:H:i',
            'shift_end_time' => 'required|date_format:H:i',
            'shift_type' => ['required', Rule::in(['day', 'night', 'emergency'])],
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the validation messages.
     */
    public function messages(): array
    {
        return [
            'doctor_id.required' => 'Please select a doctor.',
            'doctor_id.exists' => 'The selected doctor is invalid.',
            'service_id.required' => 'Please select a service.',
            'service_id.exists' => 'The selected service is invalid.',
            'month.required' => 'Month is required.',
            'month.between' => 'Month must be between 1 and 12.',
            'year.required' => 'Year is required.',
            'year.min' => 'Year cannot be in the past.',
            'planning_date.required' => 'Planning date is required.',
            'planning_date.after_or_equal' => 'Planning date cannot be in the past.',
            'shift_start_time.required' => 'Shift start time is required.',
            'shift_start_time.date_format' => 'Shift start time must be in HH:MM format.',
            'shift_end_time.required' => 'Shift end time is required.',
            'shift_end_time.date_format' => 'Shift end time must be in HH:MM format.',

            'shift_type.required' => 'Shift type is required.',
            'shift_type.in' => 'Shift type must be day, night, or emergency.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }

    /**
     * Configure custom validation rules after basic validation.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('shift_start_time') && $this->has('shift_end_time')) {
                $startTime = $this->shift_start_time;
                $endTime = $this->shift_end_time;

                // Allow overnight shifts (end time can be before start time)
                // But ensure it's not the same time
                if ($startTime === $endTime) {
                    $validator->errors()->add('shift_end_time', 'Shift end time cannot be the same as start time.');
                }

                // Validate time format and reasonable shift duration
                try {
                    $start = \Carbon\Carbon::createFromFormat('H:i', $startTime);
                    $end = \Carbon\Carbon::createFromFormat('H:i', $endTime);

                    // Calculate duration (handle overnight)
                    if ($end->lessThanOrEqualTo($start)) {
                        // Overnight shift - add 24 hours to end time for duration calc
                        $end->addDay();
                    }

                    $durationHours = $start->diffInHours($end);

                    // Validate reasonable shift duration (1-24 hours)
                    if ($durationHours < 1) {
                        $validator->errors()->add('shift_end_time', 'Shift duration must be at least 1 hour.');
                    } elseif ($durationHours > 24) {
                        $validator->errors()->add('shift_end_time', 'Shift duration cannot exceed 24 hours.');
                    }

                } catch (\Exception $e) {
                    $validator->errors()->add('shift_start_time', 'Invalid time format.');
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('planning_date')) {
            // Extract month and year from planning_date if not provided
            $date = \Carbon\Carbon::parse($this->planning_date);

            if (! $this->has('month')) {
                $this->merge(['month' => $date->month]);
            }

            if (! $this->has('year')) {
                $this->merge(['year' => $date->year]);
            }
        }
    }
}
