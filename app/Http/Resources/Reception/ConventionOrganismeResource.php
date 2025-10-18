<?php

namespace App\Http\Resources\Reception;

use Illuminate\Http\Resources\Json\JsonResource;

class ConventionOrganismeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this['id'],
            'organisme_name' => $this['organisme_name'],
            'organisme_color' => $this['organism_color'] ,
            'description' => $this['description'],
            'industry' => $this['industry'],
            'address' => $this['address'],
            'phone' => $this['phone'],
            'email' => $this['email'],
            'website' => $this['website'],
            'conventions_count' => $this['conventions_count'],
            'conventions' => collect($this['conventions'])->map(function ($convention) {
                // Process uploaded files - each convention has a single file object
                $uploadedFile = null;
                if (!empty($convention['uploaded_files'])) {
                    $file = is_array($convention['uploaded_files'])
                        ? $convention['uploaded_files'][0]
                        : $convention['uploaded_files'];

                    if ($file) {
                        $uploadedFile = [
                            'id' => $file['id'] ?? null,
                            'original_name' => $file['original_name'] ?? 'Unknown',
                            'filename' => $file['filename'] ?? null,
                            'path' => $file['path'] ?? null,
                            'folder' => $file['folder'] ?? null,
                            'size' => $file['size'] ?? 0,
                            'mime_type' => $file['mime_type'] ?? null,
                            'uploaded_at' => $file['uploaded_at'] ?? null,
                        ];
                    }
                }

                return [
                    'id' => $convention['id'],
                    'convention_name' => $convention['convention_name'],
                    'status' => $convention['status'],
                    'uploaded_file' => $uploadedFile, // Single file object, not array
                    'prestations' => $convention['prestations'],
                ];
            }),
        ];
    }
}
