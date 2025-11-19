<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // Add custom validation logic here if needed
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'status' => ['sometimes', 'string', 'in:draft,in_progress,completed,cancelled'],
            'is_global' => ['nullable', 'boolean'],
            'is_pharmacy_wide' => ['nullable', 'boolean'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'stockage_id' => ['nullable', 'integer'],
             
            'participants' => ['nullable', 'array'],
            'participants.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'participants.*.is_participant' => ['nullable', 'boolean'],
            'participants.*.is_able_to_see' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Audit name is required',
            'name.max' => 'Audit name cannot exceed 255 characters',
            'scheduled_at.after' => 'Scheduled date must be in the future',
            'participants.*.user_id.required' => 'Each participant must have a user ID',
            'participants.*.user_id.exists' => 'Selected user does not exist',
        ];
    }

    /**
     * Configure the validator instance
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate stockage_id based on inventory type
            if ($this->stockage_id && $this->is_pharmacy_wide !== null) {
                $table = $this->is_pharmacy_wide ? 'pharmacy_stockages' : 'stockages';
                $exists = \DB::table($table)->where('id', $this->stockage_id)->exists();
                
                if (!$exists) {
                    $type = $this->is_pharmacy_wide ? 'pharmacy stockages' : 'general stockages';
                    $validator->errors()->add('stockage_id', "The selected stockage does not exist in {$type}.");
                }
            }

            // Ensure service is selected when not global
            if (!$this->is_global && !$this->service_id) {
                $validator->errors()->add('service_id', 'Service is required when audit is not global.');
            }

            // Ensure inventory type is selected when not global
            if (!$this->is_global && $this->is_pharmacy_wide === null) {
                $validator->errors()->add('is_pharmacy_wide', 'Inventory type (Pharmacy or General Stock) must be selected when audit is not global.');
            }
        });
    }
}
