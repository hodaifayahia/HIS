<?php
// app/Http/Requests/UpdateRefundAuthorizationRequest.php

namespace App\Http\Requests\manager;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRefundAuthorizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'sometimes|string|max:1000',
            'requested_amount' => 'sometimes|numeric|min:0.01',
            'priority' => 'sometimes|in:low,medium,high',
            'expires_at' => 'sometimes|date|after:now',
            'notes' => 'sometimes|nullable|string|max:2000',
        ];
    }
}
