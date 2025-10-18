<?php

namespace App\Models\INFRASTRUCTURE;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\INFRASTRUCTURE\RoomType;
use App\Models\INFRASTRUCTURE\Pavilion;
use App\Models\CONFIGURATION\Service;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'room_type_id',
        'status',
        'location',
        'room_number',
        'pavilion_id',
        'service_id',
        'number_of_people'
    ];

    protected $casts = [
        'nightly_price' => 'float',
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function pavilion()
    {
        return $this->belongsTo(Pavilion::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    // ✅ ADD THIS RELATIONSHIP
    public function modalities()
    {
        return $this->hasMany(\App\Models\CONFIGURATION\Modality::class, 'physical_location_id');
    }
}
