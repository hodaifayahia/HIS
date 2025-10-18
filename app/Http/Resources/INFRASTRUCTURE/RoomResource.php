<?php

namespace App\Http\Resources\INFRASTRUCTURE;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'room_number' => $this->room_number,
            'location' => $this->location,
            'status' => $this->status,
            'number_of_people' => $this->number_of_people,
            'room_type_id' => $this->room_type_id,
            'pavilion_id' => $this->pavilion_id,
            'service_id' => $this->service_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Include relationships if they are loaded
            'room_type' => new RoomTypeResource($this->whenLoaded('roomType')),
            // 'pavilion' => new PavilionResource($this->whenLoaded('pavilion')),
            // 'service' => new ServiceResource($this->whenLoaded('service')),
        ];
    }
}
