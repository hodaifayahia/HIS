<?php

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class OrganisemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Helper to produce a public URL for an image value stored in DB
        $makePublicUrl = function ($val) {
            if (! $val) {
                return null;
            }
            // already absolute
            if (str_starts_with($val, 'http://') || str_starts_with($val, 'https://')) {
                return $val;
            }
            // already a public storage path like /storage/... or storage/...
            if (str_starts_with($val, '/storage') || str_starts_with($val, 'storage/')) {
                return asset($val);
            }
            // If value contains 'public/' prefix (common when storing on public disk), Storage::url works
            try {
                return asset(Storage::url($val));
            } catch (\Throwable $e) {
                // fallback to returning the raw value as-is
                return $val;
            }
        };

        return [
            'id' => $this->id, // Assuming 'id' is your primary key
            'name' => $this->name,
            'organism_color' => $this->organism_color,

            'legal_form' => $this->legal_form,
            'trade_register_number' => $this->trade_register_number,
            'tax_id_nif' => $this->tax_id_nif,
            'statistical_id' => $this->statistical_id,
            'article_number' => $this->article_number,
            'wilaya' => $this->wilaya,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'phone' => $this->phone,
            'fax' => $this->fax,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'website' => $this->website,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'initial_invoice_number' => $this->initial_invoice_number,
            'initial_credit_note_number' => $this->initial_credit_note_number,
            // Ensure API returns a public URL for images. Accept many stored formats.
            'logo_url' => $makePublicUrl($this->logo_url),
            'profile_image_url' => $makePublicUrl($this->profile_image_url),
            'description' => $this->description,
            'industry' => $this->industry,
            'creation_date' => $this->creation_date ? $this->creation_date->format('Y-m-d') : null, // Format date
            'number_of_employees' => $this->number_of_employees,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null, // Add timestamps
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
