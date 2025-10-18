<?php

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganismeContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
        'id' => $this->id,
        'organisme_id' => $this->organisme_id,
        'name' => $this->name,
        'phone' => $this->phone,
        'email' => $this->email,
        'role' => $this->role,
        // 'created_at' => $this->created_at, // Uncomment if you want to show
        // 'updated_at' => $this->updated_at,
    ];
    }
}
