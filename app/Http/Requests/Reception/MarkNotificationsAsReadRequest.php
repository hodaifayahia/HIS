<?php

namespace App\Http\Requests\Reception;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MarkNotificationsAsReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'notification_ids' => 'required|array|min:1',
            'notification_ids.*' => [
                'integer',
                Rule::exists('remise_request_notifications', 'id')
                    ->where('receiver_id', Auth::id())
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'notification_ids.*.exists' => 'One or more notifications do not belong to you or do not exist.',
        ];
    }
}
