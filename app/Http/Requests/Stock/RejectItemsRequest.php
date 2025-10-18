<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\StockMovementItem;

class RejectItemsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware/controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'integer|min:1',
            'rejection_reason' => 'nullable|string|max:500'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'item_ids.required' => 'At least one item must be selected for rejection.',
            'item_ids.array' => 'Item IDs must be provided as an array.',
            'item_ids.min' => 'At least one item must be selected for rejection.',
            'item_ids.*.integer' => 'Each item ID must be a valid integer.',
            'item_ids.*.min' => 'Item IDs must be positive integers.',
            'rejection_reason.string' => 'Rejection reason must be a valid text.',
            'rejection_reason.max' => 'Rejection reason cannot exceed 500 characters.'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $movementId = $this->route('movementId');
            
            // Check if all items belong to the movement and are editable
            $validItems = StockMovementItem::where('stock_movement_id', $movementId)
                                          ->whereIn('id', $this->item_ids)
                                          ->get();
            
            if ($validItems->count() !== count($this->item_ids)) {
                $validator->errors()->add('item_ids', 'Some items do not belong to this stock movement.');
            }
            
            // Check if any items are already processed
            $nonEditableItems = $validItems->filter(function ($item) {
                return !$item->isEditable();
            });
            
            if ($nonEditableItems->count() > 0) {
                $validator->errors()->add('item_ids', 'Some items have already been processed and cannot be modified.');
            }
        });
    }
}