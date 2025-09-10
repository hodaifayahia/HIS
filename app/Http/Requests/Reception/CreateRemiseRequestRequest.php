<?php

namespace App\Http\Requests\Reception;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CreateRemiseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            
            // approver optional; we validate approver != receivers in withValidator()
            'approver_id' => 'nullable|exists:users,id',
            'patient_id' => 'required|exists:patients,id',
            'message' => 'nullable|string|max:500',
            'prestations' => 'required|array|min:1',
            'prestations.*.prestation_id' => 'required|exists:prestations,id',
            'prestations.*.proposed_amount' => 'required|numeric|min:0',
            'prestations.*.contributions' => 'required|array|min:1',
            'prestations.*.contributions.*.user_id' => 'required|exists:users,id',
            'prestations.*.contributions.*.role' => 'required|in:doctor,user',
            'prestations.*.contributions.*.proposed_amount' => 'required|numeric|min:0'
        ];
    }

    public function messages(): array
    {
        return [
            'approver_id.different' => 'The approver must be different from the receiver(s).',
            'prestations.min' => 'At least one prestation is required.',
            'prestations.*.contributions.min' => 'Each prestation must have at least one contribution.',
        ];
    }

    /**
     * Ensure approver is not included in explicit receiver(s)
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();
            $receiverIds = [];

            if (!empty($data['receiver_ids']) && is_array($data['receiver_ids'])) {
                $receiverIds = array_map('intval', $data['receiver_ids']);
            } elseif (!empty($data['receiver_id'])) {
                $receiverIds = [(int)$data['receiver_id']];
            }

            if (!empty($data['approver_id']) && in_array((int)$data['approver_id'], $receiverIds, true)) {
                $validator->errors()->add('approver_id', 'The approver must be different from the receiver(s).');
            }
        });
    }

    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);
        $validated['sender_id'] = Auth::id();
        return $validated;
    }
}
