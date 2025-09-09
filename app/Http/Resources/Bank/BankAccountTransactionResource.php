<?php
// app/Http/Resources/Bank/BankAccountTransactionResource.php

namespace App\Http\Resources\Bank;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

/** @mixin \App\Models\Bank\BankAccountTransaction */
class BankAccountTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bank_account_id' => $this->bank_account_id,
            'accepted_by_user_id' => $this->accepted_by_user_id,
            'transaction_type' => $this->transaction_type,
            'transaction_type_text' => $this->transaction_type_text,
            'amount' => (float) $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'transaction_date' => $this->transaction_date?->toISOString(),
            'transaction_time' => $this->transaction_date?->format('H:i:s'),
            'description' => $this->description,
            'reference' => $this->reference,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'status_color' => $this->status_color,
            'reconciled_by_user_id' => $this->reconciled_by_user_id,
            'reconciled_at' => $this->reconciled_at?->toISOString(),
            'has_packs'=>$this->has_packs,

            // Domain-specific fields
            'Designation' => $this->Designation,
            'Payer' => $this->Payer,
            'Reference' => $this->reference,
            'Attachment' => $this->Attachment,
            'Attachment_url' => $this->getAttachmentValidationUrlAttribute() ?? ($this->Attachment ? asset('storage/' . $this->Attachment) : null),
            'Attachment_validation' => $this->Attachment_validation,
            'Attachment_validation_url' => $this->getAttachmentValidationUrlAttribute(),
            'Payment_date' => $this->Payment_date ? (
                $this->Payment_date instanceof Carbon ? $this->Payment_date->toDateString() : Carbon::parse($this->Payment_date)->toDateString()
            ) : null,
            'reference_validation' => $this->reference_validation,
            'Reason_validation' => $this->Reason_validation,

            // Relationships
            'bank_account' => $this->whenLoaded('bankAccount', function () {
                return [
                    'id' => $this->bankAccount->id,
                    'account_name' => $this->bankAccount->account_name,
                    'account_number' => $this->bankAccount->masked_account_number,
                    'currency' => $this->bankAccount->currency,
                    'bank_name' => $this->bankAccount->bank_name,
                ];
            }),

            'accepted_by' => $this->whenLoaded('acceptedBy', function () {
                return [
                    'id' => $this->acceptedBy->id,
                    'name' => $this->acceptedBy->name,
                    'email' => $this->acceptedBy->email,
                ];
            }),

            'reconciled_by' => $this->whenLoaded('reconciledBy', function () {
                return [
                    'id' => $this->reconciledBy->id,
                    'name' => $this->reconciledBy->name,
                    'email' => $this->reconciledBy->email,
                ];
            }),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
