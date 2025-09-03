<?php
// app/Http/Resources/Bank/BankAccountResource.php

namespace App\Http\Resources\Bank;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Bank\BankAccount */
class BankAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bank_id' => $this->bank_id,
            'account_name' => $this->account_name,
            'account_number' => $this->account_number,
            'masked_account_number' => $this->masked_account_number,
            'iban' => $this->iban,
            'formatted_iban' => $this->formatted_iban,
            'swift_bic' => $this->swift_bic,
            'currency' => $this->currency,
            'current_balance' => (float) $this->current_balance,
            'available_balance' => (float) $this->available_balance,
            'formatted_balance' => $this->formatted_balance,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'status_text' => $this->status_text,
            'status_color' => $this->status_color,
            'balance_status' => $this->balance_status,
            'full_account_name' => $this->full_account_name,
            
            // Bank relationship data
            'bank' => $this->whenLoaded('bank', [
                'id' => $this->bank?->id,
                'name' => $this->bank?->name,
                'code' => $this->bank?->code,
                'swift_code' => $this->bank?->swift_code,
                'logo_url' => $this->bank?->logo_url,
                'supported_currencies' => $this->bank?->supported_currencies,
                'is_active' => $this->bank?->is_active,
            ]),
            
            // Computed fields from bank
            'bank_name' => $this->bank_name,
            'bank_code' => $this->bank_code,
            'bank_swift_code' => $this->bank_swift_code,
            'bank_logo_url' => $this->bank_logo_url,
            
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
