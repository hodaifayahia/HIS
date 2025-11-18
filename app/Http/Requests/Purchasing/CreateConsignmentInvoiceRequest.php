<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

class CreateConsignmentInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'nullable|array',
            'items.*' => 'exists:consignment_reception_items,id',
            'notes' => 'nullable|string|max:1000',
            'status' => 'nullable|string|in:pending,approved,paid,cancelled',
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.exists' => 'One or more selected items do not exist',
            'status.in' => 'Invalid invoice status',
        ];
    }
}
