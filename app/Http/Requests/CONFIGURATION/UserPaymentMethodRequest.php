<?php
// filepath: d:\Projects\AppointmentSystem\AppointmentSystem-main\app\Http\Requests\CONFIGURATION\UserPaymentMethodRequest.php

namespace App\Http\Requests\CONFIGURATION;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Payment\PaymentMethodEnum;

class UserPaymentMethodRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $paymentMethodValues = PaymentMethodEnum::values();
        
        $rules = [
            'status' => 'required|string|in:active,inactive,suspended',
            'allowedMethods' => 'nullable|array',
            'allowedMethods.*' => 'string|in:' . implode(',', $paymentMethodValues),
        ];

        // For bulk assignment
        if ($this->has('userIds')) {
            $rules['userIds'] = 'required|array|min:1';
            $rules['userIds.*'] = 'integer|exists:users,id';
            $rules['paymentMethodKeys'] = 'required|array|min:1';
            $rules['paymentMethodKeys.*'] = 'string|in:' . implode(',', $paymentMethodValues);
        }

        // For new user creation (when no user ID is provided)
        if (!$this->route('user')) {
            $rules['name'] = 'required|string|max:255';
            $rules['email'] = 'required|email|unique:users,email';
            $rules['password'] = 'required|string|min:8';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'allowedMethods.*.in' => 'Invalid payment method selected.',
            'paymentMethodKeys.*.in' => 'Invalid payment method selected.',
            'userIds.*.exists' => 'One or more selected users do not exist.',
            'status.in' => 'Status must be active, inactive, or suspended.',
        ];
    }
}