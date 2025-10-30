<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductReserveRequest extends StoreProductReserveRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'status'        => ['nullable', 'in:pending,confirmed,fulfilled,cancelled,expired'],
            'cancel_reason' => ['required_if:status,cancelled', 'nullable', 'string', 'max:255'],
        ]);
    }
}
