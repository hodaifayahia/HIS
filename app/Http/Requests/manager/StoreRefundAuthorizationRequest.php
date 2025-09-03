<?php
// app/Http/Requests/StoreRefundAuthorizationRequest.php

namespace App\Http\Requests\manager;

use Illuminate\Foundation\Http\FormRequest;

class StoreRefundAuthorizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fiche_navette_item_id' => 'nullable|exists:fiche_navette_items,id',
            'item_dependency_id' => 'nullable|exists:item_dependencies,id',
            'reason' => 'required|string|max:1000',
            'requested_amount' => 'required|numeric|min:0.01',
            'priority' => 'nullable|in:low,medium,high',
            'expires_at' => 'nullable|date|after:now',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'fiche_navette_item_id.required' => 'Fiche navette item is required.',
            'fiche_navette_item_id.exists' => 'Selected fiche navette item does not exist.',
            'reason.required' => 'Refund reason is required.',
            'reason.max' => 'Reason cannot exceed 1000 characters.',
            'requested_amount.required' => 'Requested refund amount is required.',
            'requested_amount.min' => 'Requested amount must be greater than 0.',
            'priority.in' => 'Priority must be low, medium, or high.',
            'expires_at.after' => 'Expiration date must be in the future.',
        ];
    }
}
