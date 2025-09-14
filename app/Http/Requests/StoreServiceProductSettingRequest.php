<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceProductSettingRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_name' => 'required|string|max:255',
            'product_forme' => 'nullable|string|max:255',
            
            // Alert Settings
            'low_stock_threshold' => 'nullable|numeric|min:0',
            'critical_stock_threshold' => 'nullable|numeric|min:0',
            'max_stock_level' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'expiry_alert_days' => 'nullable|integer|min:1|max:365',
            'min_shelf_life_days' => 'nullable|integer|min:1',
            
            // Notification Settings
            'email_alerts' => 'boolean',
            'sms_alerts' => 'boolean',
            'alert_frequency' => 'in:immediate,daily,weekly',
            'preferred_supplier' => 'nullable|string|max:255',
            
            // Inventory Settings
            'batch_tracking' => 'boolean',
            'location_tracking' => 'boolean',
            'auto_reorder' => 'boolean',
            
            // Display Settings
            'custom_name' => 'nullable|string|max:255',
            'color_code' => 'in:default,red,orange,yellow,green,blue,purple',
            'priority' => 'in:low,normal,high,critical'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'product_name.required' => 'Product name is required',
            'low_stock_threshold.numeric' => 'Low stock threshold must be a number',
            'critical_stock_threshold.numeric' => 'Critical stock threshold must be a number',
            'max_stock_level.numeric' => 'Maximum stock level must be a number',
            'reorder_point.numeric' => 'Reorder point must be a number',
            'expiry_alert_days.integer' => 'Expiry alert days must be an integer',
            'min_shelf_life_days.integer' => 'Minimum shelf life days must be an integer',
            'alert_frequency.in' => 'Alert frequency must be immediate, daily, or weekly',
            'color_code.in' => 'Color code must be a valid option',
            'priority.in' => 'Priority must be low, normal, high, or critical'
        ];
    }
}
