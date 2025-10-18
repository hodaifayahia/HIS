<?php

namespace App\Http\Resources\B2B; // Correct namespace

use Illuminate\Http\Resources\Json\JsonResource;

class AvenantResource extends JsonResource
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
            'convention_id' => $this->convention->name,
            'convention_status' => $this->convention->status,
            'convention_data'=>$this->convention->conventionDetail,
            'description' => $this->description,
            'activation_at' => $this->activation_at ? $this->activation_at->format('d/m/Y H:i:s') : null,
            'status' => $this->status,
            'creator_id' => $this->creator_id,
            'approver_id' => $this->approver_id,
            'inactive_at' => $this->inactive_at ? $this->inactive_at->format('d/m/Y H:i:s') : null,
            'head' => $this->head,
            'updated_by_id' => $this->updated_by_id, // User ID who last updated
            'created_at' => $this->created_at->format('d/m/Y H:i:s'),
            'updated_at' => $this->updated_at->format('d/m/Y H:i:s'),
            // Conditionally add convention_status if the convention relationship is loaded
            'convention_status' => $this->whenLoaded('convention', function () {
                return $this->convention->status;
            }),
            // Include other fields as needed
        ];
    }
}