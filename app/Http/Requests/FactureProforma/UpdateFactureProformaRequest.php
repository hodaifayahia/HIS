<?php

namespace App\Http\Requests\FactureProforma;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFactureProformaRequest extends FormRequest
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
            'date' => 'nullable|date',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:draft,sent,confirmed,completed',

            // Products validation
            'products' => 'sometimes|array|min:1',
            'products.*.product_id' => 'required_with:products|exists:products,id',
            'products.*.quantity' => 'required_with:products|numeric|min:1',
            'products.*.quantity_sended' => 'nullable|numeric|min:0',
            'products.*.unit_price' => 'nullable|numeric|min:0',
            'products.*.total_price' => 'nullable|numeric|min:0',
            'products.*.notes' => 'nullable|string',

            // Existing attachments to keep
            'existing_attachments' => 'nullable|array',
            'existing_attachments.*.id' => 'nullable|integer',
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
            'products.*.product_id.required_with' => 'Product selection is required for each item.',
            'products.*.quantity.required_with' => 'Product quantity is required.',
            'products.*.quantity.min' => 'Product quantity must be at least 1.',
            'attachments.*.file.max' => 'Each attachment file must not exceed 10MB.',
            'attachments.*.file.mimes' => 'Attachment files must be of type: pdf, jpg, jpeg, png, doc, docx, xls, xlsx.',
        ];
    }
}
