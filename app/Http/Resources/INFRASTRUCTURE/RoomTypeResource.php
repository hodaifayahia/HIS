<?php

namespace App\Http\Resources\INFRASTRUCTURE;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomTypeResource extends JsonResource
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
            'name' => $this->name,
            'image_url'=> $this->image_url,
            'description' => $this->description,
            'room_type' => $this->room_type,
            'service_id' => $this->service_id,
            'service_name'=> $this->service?->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
            // Optionally, include a count of associated rooms for management UI
            // 'rooms_count' => $this->whenLoaded('rooms', fn() => $this->rooms->count()),
        ];
    }
}