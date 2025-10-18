<?php

namespace App\Http\Requests\Reception;

use App\Models\Reception\RemiseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class RejectRemiseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $remiseRequest = $this->route('remise_request');
        
        return Auth::check() && (
            Auth::id() == $remiseRequest->receiver_id || 
            Auth::id() == $remiseRequest->approver_id
        );
    }

    public function rules(): array
    {
        return [
            'comment' => 'required|string|max:500|min:10'
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'A rejection reason is required.',
            'comment.min' => 'Please provide a detailed reason for rejection (minimum 10 characters).'
        ];
    }

    protected function prepareForValidation(): void
    {
        $remiseRequest = $this->route('remise_request');
        if (!$remiseRequest || $remiseRequest->status !== RemiseRequest::STATUS_PENDING) {
            abort(422, 'Request cannot be rejected in its current state.');
        }
    }
}
