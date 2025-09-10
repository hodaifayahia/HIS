<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
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
        'email' => $this->email,
        'phone' => $this->phone,
        'role' => $this->role,
        'fichenavatte_max' => $this->fichenavatte_max,
        'is_active' => $this->is_active,
        'salary' => $this->salary ?? 0, // Add salary field
        'avatar' => $this->avatar
            ? asset(Storage::url($this->avatar))
            : asset('storage/default.png'),
        'created_at' => $this->created_at ? $this->created_at->format(config('app.date_format', 'Y-m-d H:i:s')) : null,
        'updated_at' => $this->updated_at ? $this->updated_at->format(config('app.date_format', 'Y-m-d H:i:s')) : null,
    ];
}

}
