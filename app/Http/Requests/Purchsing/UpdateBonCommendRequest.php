<?php

namespace App\Http\Requests\Purchsing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBonCommendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fournisseur_id' => 'sometimes|exists:fournisseurs,id',
            'service_demand_purchasing_id' => 'nullable|exists:service_demand_purchasings,id',
            'order_date' => 'sometimes|date',
            'expected_delivery_date' => 'nullable|date',
            'department' => 'nullable|string|max:255',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:draft,sent,confirmed,completed,cancelled',

            // Items validation
            'items' => 'sometimes|array|min:1',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|numeric|min:1',
            'items.*.quantity_desired' => 'nullable|numeric|min:0',
            'items.*.quantity_sended' => 'nullable|numeric|min:0',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.notes' => 'nullable|string',

            // Existing attachments to keep
            'existing_attachments' => 'nullable|array',
            'existing_attachments.*.path' => 'required_with:existing_attachments|string',
            'existing_attachments.*.name' => 'required_with:existing_attachments|string',
            'existing_attachments.*.description' => 'nullable|string',

            // New attachments to upload
            'attachments' => 'nullable|array',
            'attachments.*.file' => 'required_with:attachments|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240',
            'attachments.*.name' => 'nullable|string|max:255',
            'attachments.*.description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'fournisseur_id.exists' => 'The selected supplier does not exist.',
            'items.*.product_id.required_with' => 'Product selection is required for each item.',
            'items.*.quantity.required_with' => 'Item quantity is required.',
            'items.*.quantity.min' => 'Item quantity must be at least 1.',
            'attachments.*.file.max' => 'Each attachment file must not exceed 10MB.',
            'attachments.*.file.mimes' => 'Attachment files must be of type: pdf, jpg, jpeg, png, doc, docx, xls, xlsx.',
        ];
    }
}
