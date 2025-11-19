<?php

namespace App\Http\Requests\Bank;

use Illuminate\Foundation\Http\FormRequest;

class BulkUploadTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // 10MB max
            'description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.mimes' => 'File must be CSV or Excel format (.csv, .xlsx, .xls).',
            'file.max' => 'File size must not exceed 10MB.',
            'bank_account_id.required' => 'Please select a bank account.',
            'bank_account_id.exists' => 'Selected bank account does not exist.',
        ];
    }
}
