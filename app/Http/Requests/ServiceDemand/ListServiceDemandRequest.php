<?php

namespace App\Http\Requests\ServiceDemand;

use Illuminate\Foundation\Http\FormRequest;

class ListServiceDemandRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:draft,sent,approved,rejected,factureprofram,boncommend,received,cancelled',
            'service_id' => 'nullable|exists:services,id',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_field' => 'nullable|string',
            'sort_order' => 'nullable|in:asc,desc',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'Invalid status value',
            'service_id.exists' => 'Selected service does not exist',
        ];
    }

    /**
     * Get the validated data with defaults
     */
    public function validated($key = null, $default = null): array|string
    {
        $validated = parent::validated();

        return [
            'search' => $validated['search'] ?? null,
            'status' => $validated['status'] ?? null,
            'service_id' => $validated['service_id'] ?? null,
            'page' => $validated['page'] ?? 1,
            'per_page' => $validated['per_page'] ?? 15,
            'sort_field' => $validated['sort_field'] ?? 'created_at',
            'sort_order' => $validated['sort_order'] ?? 'desc',
        ];
    }
}
