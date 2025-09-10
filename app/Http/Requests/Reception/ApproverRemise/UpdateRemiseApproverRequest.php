<?php

namespace App\Http\Requests\Reception\ApproverRemise;

use Illuminate\Foundation\Http\FormRequest;
class UpdateRemiseApproverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add policy check if needed
    }

    public function rules(): array
    {
        return [
            'is_approved' => ['required', 'boolean'],
            'comments' => ['nullable', 'string', 'max:1000'],
        ];
    }
}