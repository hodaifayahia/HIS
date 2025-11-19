<?php

namespace App\Http\Requests\Purchsing;

use Illuminate\Foundation\Http\FormRequest;

class StoreBonCommendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'service_demand_purchasing_id' => 'nullable|exists:service_demand_purchasings,id',
            'order_date' => 'required|date',
            'status' => 'nullable|string',
            'approval_status' => 'nullable|string',
            'expected_delivery_date' => 'nullable|date',
            'department' => 'nullable|string|max:255',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'notes' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',

            // Items validation
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.notes' => 'nullable|string',

            // Attachments validation
            'attachments' => 'nullable|array',
            'attachments.*.file' => 'required_with:attachments|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240',
            'attachments.*.name' => 'nullable|string|max:255',
            'attachments.*.description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'fournisseur_id.required' => 'The supplier is required.',
            'fournisseur_id.exists' => 'The selected supplier does not exist.',
            'order_date.required' => 'The order date is required.',
            'items.required' => 'At least one item is required.',
            'items.*.product_id.required' => 'Product selection is required for each item.',
            'items.*.quantity.required' => 'Item quantity is required.',
            'items.*.quantity.min' => 'Item quantity must be at least 1.',
            'attachments.*.file.max' => 'Each attachment file must not exceed 10MB.',
            'attachments.*.file.mimes' => 'Attachment files must be of type: pdf, jpg, jpeg, png, doc, docx, xls, xlsx.',
        ];
    }

    public function attributes(): array
    {
        return [
            'fournisseur_id' => 'supplier',
            'service_demand_purchasing_id' => 'service demand',
            'order_date' => 'order date',
            'expected_delivery_date' => 'expected delivery date',
            'items.*.product_id' => 'product',
            'items.*.quantity' => 'quantity',
        ];
    }
}
