<?php

namespace App\Models\INFRASTRUCTURE;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CONFIGURATION\Service;


class Pavilion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'servie_id',
        'image_url'
        // 'display_color', // Uncomment if you add this column later
        // 'layout_data',   // Uncomment if you add this column later
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // 'layout_data' => 'array', // Uncomment if you add this column later and store JSON
    ];

    // Define relationships here if Pavilions will have associated Rooms, etc.
    // public function rooms()
    // {
    //     return $this->hasMany(Room::class); // Assuming a Room model exists
    // }
      public function services()
    {
        return $this->belongsToMany(Service::class, 'pavilion_service', 'pavilion_id', 'service_id');
    }
}