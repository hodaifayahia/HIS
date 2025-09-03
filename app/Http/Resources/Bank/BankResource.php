<?php
// app/Http/Resources/Bank/BankResource.php

namespace App\Http\Resources\Bank;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Bank\Bank */
class BankResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'swift_code' => $this->swift_code,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'logo_url' => $this->logo_url,
            'supported_currencies' => $this->supported_currencies,
            'supported_currencies_text' => $this->supported_currencies_text,
            'is_active' => $this->is_active,
            'status_text' => $this->status_text,
            'status_color' => $this->status_color,
            'sort_order' => $this->sort_order,
            'banques_count' => $this->whenCounted('banques'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
