<?php

namespace App\Models\INFRASTRUCTURE;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CONFIGURATION\Service;


class RoomType extends Model
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
        'image_url',
        'room_type',
        'service_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // No specific casts needed for name/description
    ];

    // Define relationships here if RoomTypes will have associated Rooms later
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
    public function service()  {
        return $this->belongsTo(Service::class);
    }
}