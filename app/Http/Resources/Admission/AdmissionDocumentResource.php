<?php

namespace App\Http\Resources\Admission;

use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionDocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'admission_id' => $this->admission_id,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'description' => $this->description,
            'verified' => (bool) $this->verified,
            'verified_by' => $this->verified_by,
            'uploaded_by' => $this->uploaded_by,
            'uploader' => $this->whenLoaded('uploadedByUser', function () {
                return [
                    'id' => $this->uploadedByUser->id,
                    'name' => $this->uploadedByUser->name,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
