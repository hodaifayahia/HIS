<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SpecializationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Generate photo URL from public/images directory
        $photoUrl = null;
        if ($this->photo) {
            // Direct path to public/images where images are copied
            $photoUrl = '/images/' . basename($this->photo);
        } else {
            $photoUrl = '/images/default.png';
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'photo_url' => $photoUrl,
            'service_id' => $this->service_id,
            'service_name' => $this->service?->name ?? 'No Service',
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}