<?php

namespace App\Models;

use App\Models\Doctor;
use App\Models\CONFIGURATION\Service;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Specialization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'name',
        'photo',
        'description',
        'service_id',
        'is_active'
    ];

    /**
     * One-to-Many relationship: Specialization has many doctors.
     */
    public function doctors()
    {
        return $this->hasMany(Doctor::class, 'specialization_id', 'id');
    }

    public function service() 
    {
        return $this->belongsTo(Service::class, 'service_id', 'id');
    }
}
