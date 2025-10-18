<?php
namespace App\Models\INFRASTRUCTURE;

use Illuminate\Database\Eloquent\Model;
use App\Models\INFRASTRUCTURE\Room;
use App\Models\CONFIGURATION\Service;
use App\Models\Patient;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bed extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'bed_identifier',
        'status',
        'current_patient_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function currentPatient()
    {
        return $this->belongsTo(Patient::class, 'current_patient_id');
    }

    // Scopes for filtering
    public function scopeByService($query, $serviceId)
    {
        return $query->whereHas('room', function ($q) use ($serviceId) {
            $q->where('service_id', $serviceId);
        });
    }

    public function scopeByRoomType($query, $roomType)
    {
        return $query->whereHas('room', function ($q) use ($roomType) {
            $q->where('room_type', $roomType);
        });
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Status constants
    const STATUS_FREE = 'free';
    const STATUS_OCCUPIED = 'occupied';
    const STATUS_RESERVED = 'reserved';

    public static function getStatuses()
    {
        return [
            self::STATUS_FREE => 'Free',
            self::STATUS_OCCUPIED => 'Occupied',
            self::STATUS_RESERVED => 'Reserved'
        ];
    }
}
