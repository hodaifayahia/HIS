<?php

namespace App\Http\Requests\Reception;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFicheNavetteItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|string|in:pending,completed,cancelled,in_progress,required',
            'base_price' => 'nullable|numeric|min:0',
            'final_price' => 'nullable|numeric|min:0',
            'patient_share' => 'nullable|numeric|min:0',
            'doctor_share' => 'nullable|numeric|min:0',
            'doctor_id' => 'nullable|integer|exists:doctors,id',
            'modality_id' => 'nullable|integer|exists:modalities,id',
            'prise_en_charge_date' => 'nullable|date',
            'dependency_prestation_ids' => 'nullable|array',
            'dependency_prestation_ids.*' => 'required|integer|exists:prestations,id',
            // Allow updating the stored default payment type and validate allowed values
            'default_payment_type' => 'nullable|in:Pré-paiement,Post-paiement,Versement',
        ];
    }

    public function messages(): array
    {
        return [
            'default_payment_type.in' => 'Payment type must be one of: Pré-paiement, Post-paiement, or Versement.',
        ];
    }
}
