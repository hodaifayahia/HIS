<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NursingEmergencyPlanningRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nurse_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'nurse')),
            ],
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

    public function messages(): array
    {
        return [
            'nurse_id.required' => 'Please select a nurse.',
            'nurse_id.exists' => 'The selected nurse is invalid.',
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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('shift_start_time') && $this->has('shift_end_time')) {
                $startTime = $this->shift_start_time;
                $endTime = $this->shift_end_time;

                if ($startTime === $endTime) {
                    $validator->errors()->add('shift_end_time', 'Shift end time cannot be the same as start time.');
                }

                try {
                    $start = \Carbon\Carbon::createFromFormat('H:i', $startTime);
                    $end = \Carbon\Carbon::createFromFormat('H:i', $endTime);

                    if ($end->lessThanOrEqualTo($start)) {
                        $end->addDay();
                    }

                    $durationHours = $start->diffInHours($end);

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

    protected function prepareForValidation(): void
    {
        if ($this->has('planning_date')) {
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
