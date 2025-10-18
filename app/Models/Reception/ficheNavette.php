<?php

namespace App\Models\Reception;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ficheNavette extends Model
{
    protected $table = 'fiche_navettes';

    protected $fillable = [
        'id',
        'patient_id',
        'companion_id',
        'creator_id',
        'fiche_date',
        'status',
        'reference',
        'total_amount',
        'notes',
        'created_by',
        'is_emergency',
        'emergency_doctor_id',
    ];

    protected $casts = [
        'fiche_date' => 'datetime',
        'arrival_time' => 'datetime',
        'departure_time' => 'datetime',
        'total_amount' => 'decimal:2',
        'is_emergency' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function companion()
    {
        return $this->belongsTo(Patient::class, 'companion_id');
    }

    public function items()
    {
        return $this->hasMany(ficheNavetteItem::class, 'fiche_navette_id');
    }

    public function emergencyDoctor()
    {
        return $this->belongsTo(Doctor::class, 'emergency_doctor_id');
    }

    // Helper method to get patient name
    public function getPatientNameAttribute()
    {
        return $this->patient ? $this->patient->first_name.' '.$this->patient->last_name : 'N/A';
    }
}
