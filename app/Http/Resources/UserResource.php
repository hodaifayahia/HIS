<?php
// app/Http/Resources/UserResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'is_active' => $this->is_active,
            'avatar' => $this->avatar ? asset('storage/' . $this->avatar) : null,
            'fichenavatte_max' => $this->fichenavatte_max,
            'salary' => $this->salary,
            'balance' => $this->balance,
          

            'specialization_ids' => $this->whenLoaded('activeSpecializations', function () {
                return $this->activeSpecializations->map(function ($item) {
                    return $item->specialization->id ?? $item->specialization_id ?? $item->id ?? null;
                })->filter()->values()->all();
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
