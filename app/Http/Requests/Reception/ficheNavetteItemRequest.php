<?php

namespace App\Http\Requests\Reception;

use Illuminate\Foundation\Http\FormRequest;

class ficheNavetteItemRequest extends FormRequest
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
            'prestation_id' => 'required|integer|exists:prestations,id',
            'appointment_id' => 'nullable|integer|exists:appointments,id',
            'status' => 'nullable|string|in:pending,completed,cancelled,in_progress,required',
            'base_price' => 'nullable|numeric|min:0',
            'user_remise_id' => 'nullable|integer|exists:users,id',
            'user_remise_share' => 'nullable|numeric|min:0|max:100',
            'doctor_share' => 'nullable|numeric|min:0',
            'doctor_id' => 'nullable|integer|exists:doctors,id',
            'final_price' => 'nullable|numeric|min:0',
            'patient_share' => 'nullable|numeric|min:0',
            'modality_id' => 'nullable|integer|exists:modalities,id',
            'prise_en_charge_date' => 'nullable|date',
            
            // Dependencies to add manually
            'dependency_prestation_ids' => 'nullable|array',
            'dependency_prestation_ids.*' => 'required|integer|exists:prestations,id',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'prestation_id.required' => 'The prestation ID is required.',
            'prestation_id.exists' => 'The selected prestation does not exist.',
            'appointment_id.exists' => 'The selected appointment does not exist.',
            'status.in' => 'The status must be one of: pending, completed, cancelled, in_progress, required.',
            'base_price.numeric' => 'The base price must be a valid number.',
            'base_price.min' => 'The base price cannot be negative.',
            'user_remise_share.max' => 'The user remise share cannot exceed 100%.',
            'doctor_id.exists' => 'The selected doctor does not exist.',
            'modality_id.exists' => 'The selected modality does not exist.',
            'prise_en_charge_date.date' => 'The prise en charge date must be a valid date.',
            'dependency_prestation_ids.array' => 'The dependency prestation IDs must be an array.',
            'dependency_prestation_ids.*.exists' => 'One or more selected dependency prestations do not exist.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Custom validation: final_price should not be less than base_price if both are provided
            if ($this->filled(['base_price', 'final_price'])) {
                if ($this->final_price < $this->base_price) {
                    $validator->errors()->add('final_price', 'The final price cannot be less than the base price.');
                }
            }

            // Custom validation: patient_share should not exceed final_price
            if ($this->filled(['final_price', 'patient_share'])) {
                if ($this->patient_share > $this->final_price) {
                    $validator->errors()->add('patient_share', 'The patient share cannot exceed the final price.');
                }
            }

            // Custom validation: doctor_share should not exceed final_price
            if ($this->filled(['final_price', 'doctor_share'])) {
                if ($this->doctor_share > $this->final_price) {
                    $validator->errors()->add('doctor_share', 'The doctor share cannot exceed the final price.');
                }
            }

            // Prevent adding the main prestation as its own dependency
            if ($this->filled(['prestation_id', 'dependency_prestation_ids'])) {
                $dependencyIds = $this->input('dependency_prestation_ids', []);
                if (in_array($this->input('prestation_id'), $dependencyIds)) {
                    $validator->errors()->add('dependency_prestation_ids', 'Cannot add the main prestation as its own dependency.');
                }
            }
        });
    }
}
